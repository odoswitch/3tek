<?php

namespace App\Controller\Admin;

use App\Entity\Commande;
use App\Repository\FileAttenteRepository;
use App\Service\LotLiberationServiceAmeliore;
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
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;
use App\Entity\User;
use App\Entity\Lot;
use App\Repository\UserRepository;
use App\Repository\LotRepository;

class CommandeCrudController extends AbstractCrudController
{
    public function __construct(
        private FileAttenteRepository $fileAttenteRepository,
        private MailerInterface $mailer,
        private LotLiberationServiceAmeliore $lotLiberationService,
        private UserRepository $userRepository,
        private LotRepository $lotRepository,
        private EntityManagerInterface $entityManager
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
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_INDEX, Action::new('valider_commande', 'Valider')
                ->linkToCrudAction('validerCommande')
                ->setIcon('fa fa-check')
                ->setCssClass('btn btn-success')
                ->displayIf(function ($entity) {
                    return $entity->getStatut() === 'en_attente';
                }))
            ->add(Crud::PAGE_INDEX, Action::new('annuler_commande', 'Annuler')
                ->linkToCrudAction('annulerCommande')
                ->setIcon('fa fa-times')
                ->setCssClass('btn btn-danger')
                ->displayIf(function ($entity) {
                    return in_array($entity->getStatut(), ['en_attente', 'reserve']);
                }))
            ->add(Crud::PAGE_INDEX, Action::new('reserver_commande', 'Réserver')
                ->linkToCrudAction('reserverCommande')
                ->setIcon('fa fa-lock')
                ->setCssClass('btn btn-warning')
                ->displayIf(function ($entity) {
                    return $entity->getStatut() === 'en_attente';
                }))
            ->add(Crud::PAGE_INDEX, Action::new('liberer_lot', 'Libérer le lot')
                ->linkToCrudAction('libererLot')
                ->setIcon('fa fa-unlock')
                ->setCssClass('btn btn-info')
                ->displayIf(function ($entity) {
                    return $entity->getLot() !== null && $entity->getStatut() === 'reserve';
                }))
            ->add(Crud::PAGE_DETAIL, Action::new('valider_commande_detail', 'Valider')
                ->linkToCrudAction('validerCommande')
                ->setIcon('fa fa-check')
                ->setCssClass('btn btn-success')
                ->displayIf(function ($entity) {
                    return $entity->getStatut() === 'en_attente';
                }))
            ->add(Crud::PAGE_DETAIL, Action::new('annuler_commande_detail', 'Annuler')
                ->linkToCrudAction('annulerCommande')
                ->setIcon('fa fa-times')
                ->setCssClass('btn btn-danger')
                ->displayIf(function ($entity) {
                    return in_array($entity->getStatut(), ['en_attente', 'reserve']);
                }))
            ->add(Crud::PAGE_DETAIL, Action::new('reserver_commande_detail', 'Réserver')
                ->linkToCrudAction('reserverCommande')
                ->setIcon('fa fa-lock')
                ->setCssClass('btn btn-warning')
                ->displayIf(function ($entity) {
                    return $entity->getStatut() === 'en_attente';
                }))
            ->add(Crud::PAGE_DETAIL, Action::new('liberer_lot_detail', 'Libérer le lot')
                ->linkToCrudAction('libererLot')
                ->setIcon('fa fa-unlock')
                ->setCssClass('btn btn-info')
                ->displayIf(function ($entity) {
                    return $entity->getLot() !== null && $entity->getStatut() === 'reserve';
                }))
            ->add(Crud::PAGE_INDEX, Action::new('nettoyer_lots', 'Nettoyer les lots orphelins')
                ->linkToCrudAction('nettoyerLotsOrphelins')
                ->setIcon('fa fa-broom')
                ->setCssClass('btn btn-secondary')
                ->createAsGlobalAction())
            ->add(Crud::PAGE_INDEX, Action::new('creer_commande_tiers', 'Créer commande pour tiers')
                ->linkToCrudAction('creerCommandeTiers')
                ->setIcon('fa fa-plus')
                ->setCssClass('btn btn-primary')
                ->createAsGlobalAction());
    }

    public function libererLot(Commande $commande): Response
    {
        $lot = $commande->getLot();

        if (!$lot) {
            $this->addFlash('danger', 'Cette commande n\'a pas de lot associé.');
            return $this->redirectToRoute('admin');
        }

        error_log("DEBUG LIBERATION MANUELLE: Libération du lot ID=" . $lot->getId());

        $this->lotLiberationService->libererLot($lot);

        $this->addFlash('success', 'Le lot a été libéré avec succès !');

        return $this->redirectToRoute('admin');
    }

    public function validerCommande(Commande $commande): Response
    {
        $lot = $commande->getLot();

        if (!$lot) {
            $this->addFlash('danger', 'Cette commande n\'a pas de lot associé.');
            return $this->redirectToRoute('admin');
        }

        error_log("DEBUG VALIDATION: Validation commande ID=" . $commande->getId());

        // Valider la commande
        $commande->setStatut('validee');
        $commande->setValidatedAt(new \DateTimeImmutable());

        // Marquer le lot comme vendu
        $lot->setStatut('vendu');
        $lot->setQuantite(0);

        $this->entityManager->persist($commande);
        $this->entityManager->persist($lot);
        $this->entityManager->flush();

        $this->addFlash('success', 'La commande a été validée avec succès !');

        return $this->redirectToRoute('admin');
    }

    public function annulerCommande(Commande $commande): Response
    {
        $lot = $commande->getLot();

        error_log("DEBUG ANNULATION ADMIN: Annulation commande ID=" . $commande->getId());

        // Annuler la commande
        $commande->setStatut('annulee');

        if ($lot) {
            // Libérer le lot avec notre service unifié
            $this->lotLiberationService->libererLot($lot);
            error_log("DEBUG ANNULATION ADMIN: Lot ID=" . $lot->getId() . " libéré");
        } else {
            error_log("DEBUG ANNULATION ADMIN: Commande ID=" . $commande->getId() . " n'a pas de lot associé - annulation simple");
        }

        $this->entityManager->persist($commande);
        $this->entityManager->flush();

        // Nettoyer automatiquement les lots orphelins
        $this->lotLiberationService->nettoyerLotsOrphelins();

        if ($lot) {
            $this->addFlash('success', 'La commande a été annulée et le lot libéré !');
        } else {
            $this->addFlash('success', 'La commande a été annulée !');
        }

        return $this->redirectToRoute('admin');
    }

    public function reserverCommande(Commande $commande): Response
    {
        $lot = $commande->getLot();

        if (!$lot) {
            $this->addFlash('danger', 'Cette commande n\'a pas de lot associé.');
            return $this->redirectToRoute('admin');
        }

        error_log("DEBUG RESERVATION ADMIN: Réservation commande ID=" . $commande->getId());

        // Réserver la commande
        $commande->setStatut('reserve');

        // Réserver le lot pour le client
        $lot->setStatut('reserve');
        $lot->setReservePar($commande->getUser());
        $lot->setReserveAt(new \DateTimeImmutable());

        $this->entityManager->persist($commande);
        $this->entityManager->persist($lot);
        $this->entityManager->flush();

        $this->addFlash('success', 'La commande a été réservée pour le client !');

        return $this->redirectToRoute('admin');
    }

    public function nettoyerLotsOrphelins(): Response
    {
        error_log("DEBUG NETTOYAGE: Nettoyage des lots orphelins demandé");

        $this->lotLiberationService->nettoyerLotsOrphelins();

        $this->addFlash('success', 'Les lots orphelins ont été nettoyés avec succès !');

        return $this->redirectToRoute('admin', [
            'crudController' => CommandeCrudController::class,
            'crudAction' => 'index'
        ]);
    }

    public function creerCommandeTiers(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $userId = $request->request->get('user_id');
            $lotId = $request->request->get('lot_id');
            $quantite = (int) $request->request->get('quantite', 1);
            $statut = $request->request->get('statut', 'en_attente');

            $user = $this->userRepository->find($userId);
            $lot = $this->lotRepository->find($lotId);

            if (!$user) {
                $this->addFlash('danger', 'Utilisateur introuvable.');
                return $this->redirectToRoute('admin');
            }

            if (!$lot) {
                $this->addFlash('danger', 'Lot introuvable.');
                return $this->redirectToRoute('admin');
            }

            if ($quantite > $lot->getQuantite()) {
                $this->addFlash('danger', 'Quantité demandée non disponible.');
                return $this->redirectToRoute('admin');
            }

            // Créer la commande
            $commande = new Commande();
            $commande->setNumeroCommande('CMD-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6)));
            $commande->setUser($user);
            $commande->setLot($lot);
            $commande->setQuantite($quantite);
            $commande->setPrixUnitaire($lot->getPrix());
            $commande->setPrixTotal($lot->getPrix() * $quantite);
            $commande->setStatut($statut);
            $commande->setCreatedAt(new \DateTimeImmutable());

            if ($statut === 'validee') {
                $commande->setValidatedAt(new \DateTimeImmutable());
                $lot->setStatut('vendu');
                $lot->setQuantite(0);
            } elseif ($statut === 'reserve') {
                $lot->setStatut('reserve');
                $lot->setReservePar($user);
                $lot->setReserveAt(new \DateTimeImmutable());
            }

            $this->entityManager->persist($commande);
            $this->entityManager->persist($lot);
            $this->entityManager->flush();

            $this->addFlash('success', "Commande créée avec succès pour {$user->getEmail()} !");

            return $this->redirectToRoute('admin');
        }

        // Afficher le formulaire
        $users = $this->userRepository->findAll();
        $lots = $this->lotRepository->findBy(['statut' => 'disponible']);

        return $this->render('admin/creer_commande_tiers.html.twig', [
            'users' => $users,
            'lots' => $lots,
        ]);
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

                // Vérifier si la commande a un lot associé
                if ($lot) {
                    $nouvelleQuantite = $lot->getQuantite() - $entityInstance->getQuantite();
                    $lot->setQuantite(max(0, $nouvelleQuantite));
                    $entityInstance->setValidatedAt(new \DateTimeImmutable());

                    $entityManager->persist($lot);
                }
            }
        }

        parent::persistEntity($entityManager, $entityInstance);
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        error_log("DEBUG UPDATE: Méthode updateEntity appelée pour commande ID=" . $entityInstance->getId());

        if ($entityInstance instanceof Commande) {
            $lot = $entityInstance->getLot();

            // Vérifier si la commande a un lot associé
            if (!$lot) {
                error_log("DEBUG UPDATE: Commande ID=" . $entityInstance->getId() . " n'a pas de lot associé - pas de traitement spécial");
                parent::updateEntity($entityManager, $entityInstance);
                return;
            }

            $ancienStatut = $entityManager->getUnitOfWork()->getOriginalEntityData($entityInstance)['statut'] ?? null;

            error_log("DEBUG UPDATE: Commande ID=" . $entityInstance->getId() . ", Ancien statut=" . ($ancienStatut ?: 'NULL') . ", Nouveau statut=" . $entityInstance->getStatut());

            // Si la commande passe à "validée" (paiement confirmé)
            if ($entityInstance->getStatut() === 'validee' && $ancienStatut !== 'validee') {
                error_log("DEBUG UPDATE: Commande validée - marquage du lot comme vendu");
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
                error_log("DEBUG UPDATE: Commande réservée - marquage du lot comme réservé");
                // Marquer le lot comme réservé pour ce client
                $lot->setStatut('reserve');
                $lot->setReservePar($entityInstance->getUser());
                $lot->setReserveAt(new \DateTimeImmutable());

                $entityManager->persist($lot);
            }

            // Si la commande est annulée
            if ($entityInstance->getStatut() === 'annulee' && $ancienStatut !== 'annulee') {
                error_log("DEBUG UPDATE: Commande annulée - libération du lot");
                // Libérer le lot et notifier le prochain dans la file d'attente
                $this->lotLiberationService->libererLot($lot);
                error_log("DEBUG UPDATE: Lot libéré avec succès");

                // Nettoyer automatiquement les lots orphelins après annulation
                error_log("DEBUG UPDATE: Nettoyage automatique des lots orphelins");
                $this->lotLiberationService->nettoyerLotsOrphelins();
                error_log("DEBUG UPDATE: Nettoyage terminé");
            }
        }

        parent::updateEntity($entityManager, $entityInstance);
        error_log("DEBUG UPDATE: Méthode updateEntity terminée");
    }

    public function deleteEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($entityInstance instanceof Commande) {
            $lot = $entityInstance->getLot();

            // Log de débogage
            error_log("DEBUG DELETE: Suppression commande ID=" . $entityInstance->getId() . ", Statut=" . $entityInstance->getStatut() . ", Lot ID=" . $lot->getId());

            // Toujours libérer le lot lors de la suppression d'une commande
            // Le service LotLiberationServiceAmeliore gère la logique :
            // - Si d'autres utilisateurs en file d'attente : réserve pour le suivant
            // - Si plus personne en file d'attente : libère pour tous
            error_log("DEBUG DELETE: Libération automatique du lot en cours...");
            $this->lotLiberationService->libererLot($lot);
            error_log("DEBUG DELETE: Lot libéré automatiquement avec succès");

            // Nettoyer automatiquement les lots orphelins après suppression
            error_log("DEBUG DELETE: Nettoyage automatique des lots orphelins");
            $this->lotLiberationService->nettoyerLotsOrphelins();
            error_log("DEBUG DELETE: Nettoyage terminé");
        }

        parent::deleteEntity($entityManager, $entityInstance);
    }
}
