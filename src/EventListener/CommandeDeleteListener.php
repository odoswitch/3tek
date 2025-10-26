<?php

namespace App\EventListener;

use App\Entity\Commande;
use App\Repository\FileAttenteRepository;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PreRemoveEventArgs;
use Doctrine\ORM\Events;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;
use Doctrine\ORM\EntityManagerInterface;

#[AsDoctrineListener(event: Events::preRemove)]
class CommandeDeleteListener
{
    public function __construct(
        private FileAttenteRepository $fileAttenteRepository,
        private MailerInterface $mailer,
        private LoggerInterface $logger,
        private EntityManagerInterface $entityManager
    ) {}

    public function preRemove(PreRemoveEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!$entity instanceof Commande) {
            return;
        }

        $this->logger->info("DEBUG LISTENER: Suppression commande ID=" . $entity->getId() . ", Statut=" . $entity->getStatut());

        // Si la commande était en statut "reserve" ou "validee", libérer le lot
        if ($entity->getStatut() === 'reserve' || $entity->getStatut() === 'validee') {
            $this->logger->info("DEBUG LISTENER: Libération du lot en cours...");

            $lot = $entity->getLot();
            $this->libererLot($lot);

            $this->logger->info("DEBUG LISTENER: Lot libéré avec succès");
        } else {
            $this->logger->info("DEBUG LISTENER: Commande pas en statut réservé/validé, pas de libération");
        }
    }

    private function libererLot($lot): void
    {
        $this->logger->info("DEBUG LIBERER: Début libération lot ID=" . $lot->getId() . ", Statut actuel=" . $lot->getStatut());

        // Restaurer la quantité (remettre à 1 si c'était un lot unique)
        if ($lot->getQuantite() == 0) {
            $lot->setQuantite(1);
        }

        $this->logger->info("DEBUG LIBERER: Quantité restaurée à " . $lot->getQuantite());

        // Chercher le premier utilisateur dans la file d'attente
        $premierEnAttente = $this->fileAttenteRepository->findFirstInQueue($lot);

        if ($premierEnAttente) {
            $this->logger->info("DEBUG LIBERER: Premier en file d'attente trouvé - User ID=" . $premierEnAttente->getUser()->getId());

            // Le lot reste "réservé" mais avec un statut spécial pour le premier de la file
            // Le premier utilisateur verra le lot comme "disponible" dans son interface
            $lot->setStatut('reserve');
            $lot->setReservePar($premierEnAttente->getUser());
            $lot->setReserveAt(new \DateTimeImmutable());

            $this->logger->info("DEBUG LIBERER: Lot réservé pour le premier utilisateur de la file");

            // Notifier l'utilisateur
            $this->notifierDisponibilite($premierEnAttente);

            // Marquer comme notifié
            $premierEnAttente->setStatut('notifie');
            $premierEnAttente->setNotifiedAt(new \DateTimeImmutable());
            $this->entityManager->persist($premierEnAttente);

            $this->logger->info("DEBUG LIBERER: Utilisateur notifié et marqué comme notifié");
        } else {
            $this->logger->info("DEBUG LIBERER: Aucun utilisateur en file d'attente - lot libéré");
            // Si personne en file d'attente, alors le lot devient vraiment disponible
            $lot->setStatut('disponible');
            $lot->setReservePar(null);
            $lot->setReserveAt(null);
        }

        // Persister les changements
        $this->entityManager->persist($lot);
        $this->entityManager->flush();
    }

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
            ));

        try {
            $this->mailer->send($email);
            $this->logger->info("DEBUG LIBERER: Email de notification envoyé à " . $user->getEmail());
        } catch (\Exception $e) {
            $this->logger->error('Erreur envoi email notification disponibilité: ' . $e->getMessage());
        }
    }
}
