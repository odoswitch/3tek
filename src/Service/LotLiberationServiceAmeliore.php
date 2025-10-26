<?php

namespace App\Service;

use App\Entity\Lot;
use App\Entity\FileAttente;
use App\Entity\User;
use App\Repository\FileAttenteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;
use Symfony\Component\Scheduler\Attribute\AsPeriodicTask;
use Symfony\Component\Scheduler\Schedule;
use Symfony\Component\Scheduler\RecurringMessage;

/**
 * Service pour gérer la libération des lots avec délai et file d'attente intelligente
 */
class LotLiberationServiceAmeliore
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private FileAttenteRepository $fileAttenteRepository,
        private MailerInterface $mailer,
        private LoggerInterface $logger,
        private Environment $twig
    ) {}

    /**
     * Libère un lot réservé de manière cohérente avec délai d'une heure
     *
     * Logique unifiée améliorée :
     * - Si quelqu'un est en file d'attente : réserver pour le premier avec délai d'1h
     * - Si personne en file d'attente : rendre disponible pour tous
     * - Notification automatique au premier utilisateur
     */
    public function libererLot(Lot $lot): void
    {
        $this->logger->info("LIBERATION: Début libération lot ID={$lot->getId()}, Statut actuel={$lot->getStatut()}");

        // Restaurer la quantité (remettre à 1 si c'était un lot unique)
        if ($lot->getQuantite() == 0) {
            $lot->setQuantite(1);
        }

        $this->logger->info("LIBERATION: Quantité restaurée à {$lot->getQuantite()}");

        // Chercher le premier utilisateur dans la file d'attente (peu importe son statut)
        $premierEnAttente = $this->fileAttenteRepository->createQueryBuilder('f')
            ->where('f.lot = :lot')
            ->andWhere('f.statut IN (:statuts)')
            ->setParameter('lot', $lot)
            ->setParameter('statuts', ['en_attente', 'en_attente_validation', 'notifie', 'delai_depasse'])
            ->orderBy('f.position', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if ($premierEnAttente) {
            $this->logger->info("LIBERATION: Premier en file d'attente trouvé - User ID={$premierEnAttente->getUser()->getId()}, Position={$premierEnAttente->getPosition()}");

            // Réserver le lot pour le premier utilisateur avec délai d'1 heure
            $lot->setStatut('reserve');
            $lot->setReservePar($premierEnAttente->getUser());
            $lot->setReserveAt(new \DateTimeImmutable());

            // Marquer le premier utilisateur comme "en_attente_validation" avec délai
            $premierEnAttente->setStatut('en_attente_validation');
            $premierEnAttente->setNotifiedAt(new \DateTimeImmutable());
            $premierEnAttente->setExpiresAt(new \DateTimeImmutable('+1 hour')); // Délai d'1 heure

            $this->entityManager->persist($premierEnAttente);

            $this->logger->info("LIBERATION: Lot réservé pour le premier utilisateur avec délai d'1h");

            // Notifier l'utilisateur qu'il a 1 heure pour valider
            $this->notifierDisponibiliteAvecDelai($premierEnAttente);

            $this->logger->info("LIBERATION: Utilisateur notifié avec délai d'1h");
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
     * Nettoie les lots orphelins (réservés sans commande ni file d'attente)
     * Cette méthode peut être appelée périodiquement pour corriger les incohérences
     */
    public function nettoyerLotsOrphelins(): void
    {
        $this->logger->info("NETTOYAGE: Recherche des lots orphelins");

        // Trouver tous les lots réservés
        $lotsReserves = $this->entityManager->getRepository(Lot::class)
            ->createQueryBuilder('l')
            ->where('l.statut = :statut')
            ->setParameter('statut', 'reserve')
            ->getQuery()
            ->getResult();

        foreach ($lotsReserves as $lot) {
            // Vérifier s'il y a des commandes actives pour ce lot
            $nbCommandes = $this->entityManager->getRepository(\App\Entity\Commande::class)
                ->createQueryBuilder('c')
                ->select('COUNT(c.id)')
                ->where('c.lot = :lot')
                ->andWhere('c.statut != :statut')
                ->setParameter('lot', $lot)
                ->setParameter('statut', 'annulee')
                ->getQuery()
                ->getSingleScalarResult();

            // Vérifier s'il y a des files d'attente pour ce lot
            $nbFilesAttente = $this->fileAttenteRepository
                ->createQueryBuilder('f')
                ->select('COUNT(f.id)')
                ->where('f.lot = :lot')
                ->setParameter('lot', $lot)
                ->getQuery()
                ->getSingleScalarResult();

            // Si aucune commande active et aucune file d'attente, libérer le lot
            if ($nbCommandes == 0 && $nbFilesAttente == 0) {
                $this->logger->info("NETTOYAGE: Lot orphelin trouvé ID={$lot->getId()} - Libération automatique");
                
                $lot->setStatut('disponible');
                $lot->setReservePar(null);
                $lot->setReserveAt(null);
                
                $this->entityManager->persist($lot);
            }
        }

        $this->entityManager->flush();
        $this->logger->info("NETTOYAGE: Nettoyage des lots orphelins terminé");
    }

    /**
     * Vérifie les délais expirés et passe au suivant dans la file d'attente
     * Cette méthode sera appelée périodiquement par le scheduler
     */
    public function verifierDelaisExpires(): void
    {
        $this->logger->info("SCHEDULER: Vérification des délais expirés");

        // Trouver tous les utilisateurs en attente de validation avec délai expiré
        $utilisateursExpires = $this->fileAttenteRepository->createQueryBuilder('f')
            ->where('f.statut = :statut')
            ->andWhere('f.expiresAt < :now')
            ->setParameter('statut', 'en_attente_validation')
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()
            ->getResult();

        foreach ($utilisateursExpires as $fileAttente) {
            $this->logger->info("SCHEDULER: Délai expiré pour utilisateur {$fileAttente->getUser()->getEmail()}");

            // Notifier l'utilisateur qu'il a dépassé le délai
            $this->notifierDelaiDepasse($fileAttente);

            // Marquer comme expiré
            $fileAttente->setStatut('delai_depasse');
            $fileAttente->setExpiredAt(new \DateTimeImmutable());

            // Passer au suivant dans la file d'attente
            $this->passerAuSuivant($fileAttente->getLot());

            $this->entityManager->persist($fileAttente);
        }

        $this->entityManager->flush();
        $this->logger->info("SCHEDULER: Vérification terminée - " . count($utilisateursExpires) . " délais expirés traités");
    }

    /**
     * Passe au suivant dans la file d'attente
     */
    private function passerAuSuivant(Lot $lot): void
    {
        $this->logger->info("SCHEDULER: Passage au suivant pour lot ID={$lot->getId()}");

        // Chercher le prochain utilisateur en file d'attente
        $prochainEnAttente = $this->fileAttenteRepository->createQueryBuilder('f')
            ->where('f.lot = :lot')
            ->andWhere('f.statut = :statut')
            ->setParameter('lot', $lot)
            ->setParameter('statut', 'en_attente')
            ->orderBy('f.position', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if ($prochainEnAttente) {
            $this->logger->info("SCHEDULER: Prochain utilisateur trouvé - {$prochainEnAttente->getUser()->getEmail()}");

            // Réserver le lot pour le prochain utilisateur
            $lot->setReservePar($prochainEnAttente->getUser());
            $lot->setReserveAt(new \DateTimeImmutable());

            // Marquer le prochain utilisateur comme "en_attente_validation" avec délai
            $prochainEnAttente->setStatut('en_attente_validation');
            $prochainEnAttente->setNotifiedAt(new \DateTimeImmutable());
            $prochainEnAttente->setExpiresAt(new \DateTimeImmutable('+1 hour'));

            $this->entityManager->persist($lot);
            $this->entityManager->persist($prochainEnAttente);

            // Notifier le prochain utilisateur
            $this->notifierDisponibiliteAvecDelai($prochainEnAttente);

            $this->logger->info("SCHEDULER: Prochain utilisateur notifié avec délai d'1h");
        } else {
            $this->logger->info("SCHEDULER: Aucun utilisateur suivant - lot libéré pour tous");

            // Aucun utilisateur suivant, libérer le lot pour tous
            $lot->setStatut('disponible');
            $lot->setReservePar(null);
            $lot->setReserveAt(null);

            $this->entityManager->persist($lot);
        }
    }

    /**
     * Envoie un email pour notifier qu'un lot est disponible avec délai d'1h
     */
    private function notifierDisponibiliteAvecDelai(FileAttente $fileAttente): void
    {
        $user = $fileAttente->getUser();
        $lot = $fileAttente->getLot();

        try {
            // Générer l'URL du lot
            $lotUrl = 'http://localhost:8080/lot/' . $lot->getId();

            // Générer l'URL du logo
            $logoUrl = 'http://localhost:8080/images/logo.png';

            // Rendre le template Twig
            $htmlContent = $this->twig->render('emails/lot_disponible_avec_delai.html.twig', [
                'user' => $user,
                'lot' => $lot,
                'position' => $fileAttente->getPosition(),
                'lotUrl' => $lotUrl,
                'logoUrl' => $logoUrl,
                'expiresAt' => $fileAttente->getExpiresAt()
            ]);

            $email = (new Email())
                ->from('noreply@3tek-europe.com')
                ->to($user->getEmail())
                ->subject('⏰ Lot disponible - Vous avez 1h pour commander !')
                ->html($htmlContent);

            $this->mailer->send($email);
            $this->logger->info("LIBERATION: Email de notification avec délai envoyé à {$user->getEmail()}");
        } catch (\Exception $e) {
            $this->logger->error("LIBERATION: Erreur envoi email à {$user->getEmail()}: " . $e->getMessage());
        }
    }

    /**
     * Envoie un email pour notifier qu'un délai a été dépassé
     */
    private function notifierDelaiDepasse(FileAttente $fileAttente): void
    {
        $user = $fileAttente->getUser();
        $lot = $fileAttente->getLot();

        try {
            // Générer l'URL du lot
            $lotUrl = 'http://localhost:8080/lot/' . $lot->getId();

            // Générer l'URL du logo
            $logoUrl = 'http://localhost:8080/images/logo.png';

            // Rendre le template Twig
            $htmlContent = $this->twig->render('emails/delai_depasse.html.twig', [
                'user' => $user,
                'lot' => $lot,
                'position' => $fileAttente->getPosition(),
                'lotUrl' => $lotUrl,
                'logoUrl' => $logoUrl,
                'expiredAt' => $fileAttente->getExpiresAt()
            ]);

            $email = (new Email())
                ->from('noreply@3tek-europe.com')
                ->to($user->getEmail())
                ->subject('⏰ Délai dépassé - Le lot est passé au suivant')
                ->html($htmlContent);

            $this->mailer->send($email);
            $this->logger->info("SCHEDULER: Email délai dépassé envoyé à {$user->getEmail()}");
        } catch (\Exception $e) {
            $this->logger->error("SCHEDULER: Erreur envoi email délai dépassé à {$user->getEmail()}: " . $e->getMessage());
        }
    }
}
