<?php

namespace App\Service;

use App\Entity\Lot;
use App\Entity\FileAttente;
use App\Repository\FileAttenteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Twig\Environment;

/**
 * Service pour gérer la libération des lots et les notifications
 */
class LotLiberationService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private FileAttenteRepository $fileAttenteRepository,
        private MailerInterface $mailer,
        private LoggerInterface $logger,
        private Environment $twig,
        private UrlGeneratorInterface $urlGenerator,
        private ParameterBagInterface $params
    ) {}

    /**
     * Libère un lot réservé de manière cohérente
     * 
     * Logique unifiée :
     * - Si quelqu'un est en file d'attente : réserver automatiquement pour le premier
     * - Si personne en file d'attente : rendre disponible pour tous
     */
    public function libererLot(Lot $lot): void
    {
        $this->logger->info("LIBERATION: Début libération lot ID={$lot->getId()}, Statut actuel={$lot->getStatut()}");

        // Restaurer la quantité (remettre à 1 si c'était un lot unique)
        if ($lot->getQuantite() == 0) {
            $lot->setQuantite(1);
        }

        $this->logger->info("LIBERATION: Quantité restaurée à {$lot->getQuantite()}");

        // Chercher le premier utilisateur dans la file d'attente
        $premierEnAttente = $this->fileAttenteRepository->findFirstInQueue($lot);

        if ($premierEnAttente) {
            $this->logger->info("LIBERATION: Premier en file d'attente trouvé - User ID={$premierEnAttente->getUser()->getId()}");

            // Réserver automatiquement le lot pour le premier utilisateur en file d'attente
            $lot->setStatut('reserve');
            $lot->setReservePar($premierEnAttente->getUser());
            $lot->setReserveAt(new \DateTimeImmutable());

            $this->logger->info("LIBERATION: Lot réservé automatiquement pour le premier utilisateur de la file");

            // Notifier l'utilisateur
            $this->notifierDisponibilite($premierEnAttente);

            // Marquer comme notifié
            $premierEnAttente->setStatut('notifie');
            $premierEnAttente->setNotifiedAt(new \DateTimeImmutable());
            $this->entityManager->persist($premierEnAttente);

            $this->logger->info("LIBERATION: Utilisateur notifié et marqué comme notifié");
        } else {
            $this->logger->info("LIBERATION: Aucun utilisateur en file d'attente - lot libéré pour tous");

            // Si personne en file d'attente, alors le lot devient vraiment disponible
            $lot->setStatut('disponible');
            $lot->setReservePar(null);
            $lot->setReserveAt(null);
        }

        // Persister les changements
        $this->entityManager->persist($lot);
        $this->entityManager->flush();

        $this->logger->info("LIBERATION: Libération terminée - Statut final={$lot->getStatut()}");
    }

    /**
     * Envoie un email pour notifier qu'un lot est disponible
     */
    private function notifierDisponibilite(FileAttente $fileAttente): void
    {
        $user = $fileAttente->getUser();
        $lot = $fileAttente->getLot();

        try {
            // Générer l'URL du lot dynamiquement
            $lotUrl = $this->urlGenerator->generate('app_lot_view', ['id' => $lot->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

            // Générer l'URL du logo dynamiquement
            $logoUrl = rtrim($this->params->get('app.base_url'), '/') . '/images/logo.png';

            // Rendre le template Twig
            $htmlContent = $this->twig->render('emails/lot_disponible_notification.html.twig', [
                'user' => $user,
                'lot' => $lot,
                'position' => $fileAttente->getPosition(),
                'lotUrl' => $lotUrl,
                'logoUrl' => $logoUrl
            ]);

            $email = (new Email())
                ->from('noreply@3tek-europe.com')
                ->to($user->getEmail())
                ->subject('🎉 Lot disponible - 3Tek Europe')
                ->html($htmlContent);

            $this->mailer->send($email);
            $this->logger->info("LIBERATION: Email de notification envoyé à {$user->getEmail()}");
        } catch (\Exception $e) {
            $this->logger->error("LIBERATION: Erreur envoi email à {$user->getEmail()}: " . $e->getMessage());
        }
    }
}
