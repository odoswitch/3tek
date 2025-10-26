<?php

namespace App\Controller\Admin;

use App\Entity\Commande;
use App\Repository\FileAttenteRepository;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;

class CommandeCrudController extends AbstractCrudController
{
    public function __construct(
        private FileAttenteRepository $fileAttenteRepository,
        private MailerInterface $mailer
    ) {}

    public static function getEntityFqcn(): string
    {
        return Commande::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Commande')
            ->setEntityLabelInPlural('Commandes')
            ->setDefaultSort(['createdAt' => 'DESC']);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL);
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->onlyOnIndex(),
            TextField::new('numeroCommande', 'N° Commande'),
            TextField::new('user.email', 'Client')->onlyOnIndex(),
            TextField::new('user.name', 'Prénom')->onlyOnDetail(),
            TextField::new('user.lastname', 'Nom')->onlyOnDetail(),
            TextField::new('user.phone', 'Téléphone')->onlyOnDetail(),
            TextField::new('user.office', 'Entreprise')->onlyOnDetail(),
            TextField::new('lot.name', 'Lot'),
            NumberField::new('quantite', 'Quantité'),
            NumberField::new('prixUnitaire', 'Prix unitaire')->setNumDecimals(2)->formatValue(function ($value) {
                return number_format($value, 2, ',', ' ') . ' €';
            }),
            NumberField::new('prixTotal', 'Total')->setNumDecimals(2)->formatValue(function ($value) {
                return number_format($value, 2, ',', ' ') . ' €';
            }),
            ChoiceField::new('statut', 'Statut')
                ->setChoices([
                    'En attente' => 'en_attente',
                    'Réservé' => 'reserve',
                    'Validée' => 'validee',
                    'Annulée' => 'annulee',
                ])
                ->renderAsBadges([
                    'en_attente' => 'warning',
                    'reserve' => 'info',
                    'validee' => 'success',
                    'annulee' => 'danger',
                ]),
            DateTimeField::new('createdAt', 'Date de commande')->setFormat('dd/MM/yyyy HH:mm'),
            DateTimeField::new('validatedAt', 'Date de validation')->setFormat('dd/MM/yyyy HH:mm')->hideOnIndex(),
        ];
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($entityInstance instanceof Commande) {
            // Si la commande passe à "validée", réduire le stock
            if ($entityInstance->getStatut() === 'validee' && !$entityInstance->getValidatedAt()) {
                $lot = $entityInstance->getLot();
                $nouvelleQuantite = $lot->getQuantite() - $entityInstance->getQuantite();
                $lot->setQuantite(max(0, $nouvelleQuantite));
                $entityInstance->setValidatedAt(new \DateTimeImmutable());

                $entityManager->persist($lot);
            }
        }

        parent::persistEntity($entityManager, $entityInstance);
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($entityInstance instanceof Commande) {
            $lot = $entityInstance->getLot();
            $ancienStatut = $entityManager->getUnitOfWork()->getOriginalEntityData($entityInstance)['statut'] ?? null;

            // Si la commande passe à "validée" (paiement confirmé)
            if ($entityInstance->getStatut() === 'validee' && $ancienStatut !== 'validee') {
                // Si le lot était réservé, le marquer comme vendu
                if ($lot->getStatut() === 'reserve') {
                    $lot->setStatut('vendu');
                    $lot->setQuantite(0);
                }
                $entityInstance->setValidatedAt(new \DateTimeImmutable());

                $entityManager->persist($lot);
            }

            // Si la commande passe de "en_attente" à "reserve" (paiement reçu mais lot pas encore vendu)
            if ($entityInstance->getStatut() === 'reserve' && $ancienStatut === 'en_attente') {
                // Marquer le lot comme réservé pour ce client
                $lot->setStatut('reserve');
                $lot->setReservePar($entityInstance->getUser());
                $lot->setReserveAt(new \DateTimeImmutable());

                $entityManager->persist($lot);
            }

            // Si la commande est annulée
            if ($entityInstance->getStatut() === 'annulee' && $ancienStatut !== 'annulee') {
                // Libérer le lot et notifier le prochain dans la file d'attente
                $this->libererLot($lot, $entityManager);
            }
        }

        parent::updateEntity($entityManager, $entityInstance);
    }

    public function deleteEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($entityInstance instanceof Commande) {
            $lot = $entityInstance->getLot();

            // Log de débogage
            error_log("DEBUG DELETE: Suppression commande ID=" . $entityInstance->getId() . ", Statut=" . $entityInstance->getStatut() . ", Lot ID=" . $lot->getId());

            // Si la commande était en statut "reserve" ou "validee", libérer le lot
            if ($entityInstance->getStatut() === 'reserve' || $entityInstance->getStatut() === 'validee') {
                error_log("DEBUG DELETE: Libération du lot en cours...");
                // Libérer le lot et notifier le prochain dans la file d'attente
                $this->libererLot($lot, $entityManager);
                error_log("DEBUG DELETE: Lot libéré avec succès");
            } else {
                error_log("DEBUG DELETE: Commande pas en statut réservé/validé, pas de libération");
            }
        }

        parent::deleteEntity($entityManager, $entityInstance);
    }

    public function removeEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($entityInstance instanceof Commande) {
            $lot = $entityInstance->getLot();

            // Log de débogage
            error_log("DEBUG REMOVE: Suppression commande ID=" . $entityInstance->getId() . ", Statut=" . $entityInstance->getStatut() . ", Lot ID=" . $lot->getId());

            // Si la commande était en statut "reserve" ou "validee", libérer le lot
            if ($entityInstance->getStatut() === 'reserve' || $entityInstance->getStatut() === 'validee') {
                error_log("DEBUG REMOVE: Libération du lot en cours...");
                // Libérer le lot et notifier le prochain dans la file d'attente
                $this->libererLot($lot, $entityManager);
                error_log("DEBUG REMOVE: Lot libéré avec succès");
            } else {
                error_log("DEBUG REMOVE: Commande pas en statut réservé/validé, pas de libération");
            }
        }

        parent::removeEntity($entityManager, $entityInstance);
    }

    /**
     * Libère un lot réservé et notifie le prochain utilisateur dans la file d'attente
     */
    private function libererLot($lot, EntityManagerInterface $entityManager): void
    {
        error_log("DEBUG LIBERER: Début libération lot ID=" . $lot->getId() . ", Statut actuel=" . $lot->getStatut());

        // Remettre le lot comme disponible ET restaurer la quantité
        $lot->setStatut('disponible');
        $lot->setReservePar(null);
        $lot->setReserveAt(null);

        // Restaurer la quantité (remettre à 1 si c'était un lot unique)
        if ($lot->getQuantite() == 0) {
            $lot->setQuantite(1);
        }

        error_log("DEBUG LIBERER: Lot mis à jour - Statut=disponible, Quantité=" . $lot->getQuantite());

        // Chercher le premier utilisateur dans la file d'attente
        $premierEnAttente = $this->fileAttenteRepository->findFirstInQueue($lot);

        if ($premierEnAttente) {
            // Notifier l'utilisateur
            $this->notifierDisponibilite($premierEnAttente);

            // Marquer comme notifié
            $premierEnAttente->setStatut('notifie');
            $premierEnAttente->setNotifiedAt(new \DateTimeImmutable());
            $entityManager->persist($premierEnAttente);
        }

        $entityManager->persist($lot);
    }

    /**
     * Envoie un email pour notifier qu'un lot est disponible
     */
    private function notifierDisponibilite($fileAttente): void
    {
        $user = $fileAttente->getUser();
        $lot = $fileAttente->getLot();

        $email = (new Email())
            ->from(new Address('noreply@3tek-europe.com', '3Tek-Europe'))
            ->to($user->getEmail())
            ->replyTo('noreply@3tek-europe.com')
            ->subject('Le lot "' . $lot->getName() . '" est maintenant disponible !')
            ->html(sprintf(
                '<h2>Bonne nouvelle !</h2>
                <p>Bonjour %s,</p>
                <p>Le lot <strong>%s</strong> pour lequel vous étiez en file d\'attente est maintenant disponible.</p>
                <p>Connectez-vous rapidement à votre espace client pour le réserver avant qu\'il ne soit pris par quelqu\'un d\'autre.</p>
                <p><a href="%s" style="display: inline-block; padding: 12px 30px; background: #0066cc; color: white; text-decoration: none; border-radius: 5px;">Voir le lot</a></p>
                <p>Cordialement,<br>L\'équipe 3Tek-Europe</p>',
                $user->getName(),
                $lot->getName(),
                'https://app.3tek-europe.com/lot/' . $lot->getId()
            ))
            ->text(sprintf(
                "Bonjour %s,\n\nLe lot \"%s\" pour lequel vous étiez en file d'attente est maintenant disponible.\n\nConnectez-vous rapidement à votre espace client pour le réserver.\n\nCordialement,\nL'équipe 3Tek-Europe",
                $user->getName(),
                $lot->getName()
            ));

        // Ajouter des en-têtes
        $headers = $email->getHeaders();
        $headers->addTextHeader('X-Mailer', '3Tek-Europe Notification System');
        $headers->addTextHeader('X-Priority', '2');
        $headers->addTextHeader('Importance', 'High');

        try {
            $this->mailer->send($email);
        } catch (\Exception $e) {
            // Logger l'erreur mais ne pas bloquer le processus
            error_log('Erreur envoi email notification disponibilité: ' . $e->getMessage());
        }
    }
}
