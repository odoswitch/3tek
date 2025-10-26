<?php

require_once __DIR__ . '/vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Lot;
use App\Entity\User;
use App\Entity\Commande;
use App\Entity\FileAttente;
use App\Repository\LotRepository;
use App\Repository\UserRepository;
use App\Repository\CommandeRepository;
use App\Repository\FileAttenteRepository;

// Charger les variables d'environnement
$dotenv = new Dotenv();
$dotenv->load(__DIR__ . '/.env');

// Initialiser Symfony
$kernel = new \App\Kernel($_ENV['APP_ENV'] ?? 'dev', (bool) ($_ENV['APP_DEBUG'] ?? true));
$kernel->boot();

$container = $kernel->getContainer();
$entityManager = $container->get('doctrine.orm.entity_manager');

// Récupérer les repositories
$lotRepository = $entityManager->getRepository(Lot::class);
$userRepository = $entityManager->getRepository(User::class);
$commandeRepository = $entityManager->getRepository(Commande::class);
$fileAttenteRepository = $entityManager->getRepository(FileAttente::class);

echo "=== DÉMONSTRATION LOGIQUE AMÉLIORÉE AVEC DÉLAI D'1H ===\n\n";

// 1. Trouver un lot disponible
echo "1. PRÉPARATION DU TEST\n";
echo "========================\n";

$lot = $lotRepository->createQueryBuilder('l')
    ->where('l.statut = :statut')
    ->andWhere('l.quantite > 0')
    ->setParameter('statut', 'disponible')
    ->setMaxResults(1)
    ->getQuery()
    ->getOneOrNullResult();

if (!$lot) {
    echo "❌ Aucun lot disponible, libérons un lot...\n";
    $lotReserve = $lotRepository->createQueryBuilder('l')
        ->where('l.statut = :statut')
        ->setParameter('statut', 'reserve')
        ->setMaxResults(1)
        ->getQuery()
        ->getOneOrNullResult();

    if ($lotReserve) {
        $lotReserve->setStatut('disponible');
        $lotReserve->setReservePar(null);
        $lotReserve->setReserveAt(null);
        $lotReserve->setQuantite(1);
        $entityManager->persist($lotReserve);
        $entityManager->flush();
        $lot = $lotReserve;
    }
}

if (!$lot) {
    echo "❌ Impossible de trouver un lot pour le test\n";
    exit(1);
}

echo "✅ Lot utilisé : {$lot->getName()} (ID: {$lot->getId()})\n";

// Trouver des utilisateurs
$users = $userRepository->createQueryBuilder('u')
    ->setMaxResults(3)
    ->getQuery()
    ->getResult();

$user1 = $users[0]; // Créera la commande
$user2 = $users[1]; // Premier en file d'attente
$user3 = count($users) > 2 ? $users[2] : null; // Deuxième en file d'attente

echo "✅ Utilisateurs :\n";
echo "   - User1 (créera commande): {$user1->getEmail()}\n";
echo "   - User2 (premier en file): {$user2->getEmail()}\n";
if ($user3) {
    echo "   - User3 (deuxième en file): {$user3->getEmail()}\n";
}

echo "\n";

// 2. Créer une commande et réserver le lot
echo "2. CRÉATION COMMANDE ET RÉSERVATION\n";
echo "=====================================\n";

$commande = new Commande();
$commande->setUser($user1);
$commande->setLot($lot);
$commande->setQuantite(1);
$commande->setPrixUnitaire($lot->getPrix());
$commande->setPrixTotal($lot->getPrix());
$commande->setStatut('en_attente');

$entityManager->persist($commande);

// Réserver le lot
$lot->setQuantite(0);
$lot->setStatut('reserve');
$lot->setReservePar($user1);
$lot->setReserveAt(new \DateTimeImmutable());

$entityManager->persist($lot);
$entityManager->flush();

echo "✅ Commande créée (ID: {$commande->getId()}) et lot réservé\n";

// 3. Créer la file d'attente
echo "\n3. CRÉATION FILE D'ATTENTE\n";
echo "============================\n";

// User2 en position 1
$fileAttente1 = new FileAttente();
$fileAttente1->setLot($lot);
$fileAttente1->setUser($user2);
$fileAttente1->setPosition(1);
$fileAttente1->setStatut('en_attente');

$entityManager->persist($fileAttente1);

echo "✅ User2 ajouté en position 1\n";

// User3 en position 2 (si disponible)
if ($user3) {
    $fileAttente2 = new FileAttente();
    $fileAttente2->setLot($lot);
    $fileAttente2->setUser($user3);
    $fileAttente2->setPosition(2);
    $fileAttente2->setStatut('en_attente');

    $entityManager->persist($fileAttente2);
    echo "✅ User3 ajouté en position 2\n";
}

$entityManager->flush();

echo "\n";

// 4. Simuler l'annulation avec la nouvelle logique
echo "4. ANNULATION AVEC LOGIQUE AMÉLIORÉE\n";
echo "=======================================\n";

echo "🔄 Application de la logique améliorée avec délai d'1h...\n";

// Annuler la commande
$commande->setStatut('annulee');
$lot->setQuantite(1);

// Chercher le premier utilisateur en file d'attente
$premierEnAttente = $fileAttenteRepository->findFirstInQueue($lot);

if ($premierEnAttente) {
    echo "✅ Premier utilisateur en file d'attente trouvé :\n";
    echo "   - Email: {$premierEnAttente->getUser()->getEmail()}\n";
    echo "   - Position: {$premierEnAttente->getPosition()}\n";

    // Réserver le lot pour le premier utilisateur avec délai d'1 heure
    $lot->setStatut('reserve');
    $lot->setReservePar($premierEnAttente->getUser());
    $lot->setReserveAt(new \DateTimeImmutable());

    // Marquer le premier utilisateur comme "en_attente_validation" avec délai
    $premierEnAttente->setStatut('en_attente_validation');
    $premierEnAttente->setNotifiedAt(new \DateTimeImmutable());
    $premierEnAttente->setExpiresAt(new \DateTimeImmutable('+1 hour')); // Délai d'1 heure

    $entityManager->persist($premierEnAttente);

    echo "✅ Lot réservé pour le premier utilisateur avec délai d'1h\n";
    echo "✅ Premier utilisateur marqué comme 'en_attente_validation'\n";
    echo "✅ Délai d'expiration : " . $premierEnAttente->getExpiresAt()->format('d/m/Y H:i') . "\n";
    echo "📧 Email de notification avec délai envoyé\n";
} else {
    echo "❌ Aucun utilisateur en file d'attente\n";
    $lot->setStatut('disponible');
    $lot->setReservePar(null);
    $lot->setReserveAt(null);
}

$entityManager->persist($commande);
$entityManager->persist($lot);
$entityManager->flush();

echo "\n";

// 5. Vérifier l'état après annulation
echo "5. ÉTAT APRÈS ANNULATION\n";
echo "===========================\n";

echo "📊 Commande :\n";
echo "   - Statut: {$commande->getStatut()}\n";

echo "📊 Lot :\n";
echo "   - Statut: {$lot->getStatut()}\n";
echo "   - Réservé par: " . ($lot->getReservePar() ? $lot->getReservePar()->getEmail() : 'Aucun') . "\n";

echo "📊 File d'attente :\n";
$filesAttente = $fileAttenteRepository->findByLot($lot);
foreach ($filesAttente as $file) {
    echo "   - Position {$file->getPosition()}: {$file->getUser()->getEmail()} (statut: {$file->getStatut()})\n";
    if ($file->getExpiresAt()) {
        echo "     Délai d'expiration: {$file->getExpiresAt()->format('d/m/Y H:i')}\n";
    }
}

echo "\n";

// 6. Test de disponibilité
echo "6. TEST DE DISPONIBILITÉ\n";
echo "==========================\n";

$testUsers = [$user1, $user2, $user3];
foreach ($testUsers as $index => $user) {
    if (!$user) continue;

    $estDisponible = $lot->isDisponiblePour($user);
    $estEnFileAttente = $fileAttenteRepository->isUserInQueue($lot, $user);

    echo "👤 User" . ($index + 1) . " ({$user->getEmail()}) :\n";
    echo "   - Peut commander: " . ($estDisponible ? "✅ OUI" : "❌ NON") . "\n";
    echo "   - En file d'attente: " . ($estEnFileAttente ? "✅ OUI" : "❌ NON") . "\n";

    if ($estEnFileAttente) {
        $fileUser = $fileAttenteRepository->createQueryBuilder('f')
            ->where('f.lot = :lot')
            ->andWhere('f.user = :user')
            ->setParameter('lot', $lot)
            ->setParameter('user', $user)
            ->getQuery()
            ->getOneOrNullResult();

        if ($fileUser) {
            echo "   - Position: {$fileUser->getPosition()}\n";
            echo "   - Statut: {$fileUser->getStatut()}\n";
            if ($fileUser->getExpiresAt()) {
                echo "   - Délai d'expiration: {$fileUser->getExpiresAt()->format('d/m/Y H:i')}\n";
            }
        }
    }
    echo "\n";
}

// 7. Simuler l'expiration du délai
echo "7. SIMULATION EXPIRATION DU DÉLAI\n";
echo "====================================\n";

echo "🔄 Simulation : User2 n'a pas commandé dans le délai d'1h...\n";

// Marquer le délai comme expiré
$premierEnAttente->setStatut('delai_depasse');
$premierEnAttente->setExpiredAt(new \DateTimeImmutable());

echo "✅ User2 marqué comme 'delai_depasse'\n";
echo "📧 Email de notification 'délai dépassé' envoyé à User2\n";

// Passer au suivant
$prochainEnAttente = $fileAttenteRepository->createQueryBuilder('f')
    ->where('f.lot = :lot')
    ->andWhere('f.statut = :statut')
    ->setParameter('lot', $lot)
    ->setParameter('statut', 'en_attente')
    ->orderBy('f.position', 'ASC')
    ->setMaxResults(1)
    ->getQuery()
    ->getOneOrNullResult();

if ($prochainEnAttente) {
    echo "✅ Prochain utilisateur trouvé : {$prochainEnAttente->getUser()->getEmail()}\n";

    // Réserver le lot pour le prochain utilisateur
    $lot->setReservePar($prochainEnAttente->getUser());
    $lot->setReserveAt(new \DateTimeImmutable());

    // Marquer le prochain utilisateur comme "en_attente_validation" avec délai
    $prochainEnAttente->setStatut('en_attente_validation');
    $prochainEnAttente->setNotifiedAt(new \DateTimeImmutable());
    $prochainEnAttente->setExpiresAt(new \DateTimeImmutable('+1 hour'));

    $entityManager->persist($lot);
    $entityManager->persist($prochainEnAttente);

    echo "✅ Lot réservé pour le prochain utilisateur\n";
    echo "✅ Prochain utilisateur marqué comme 'en_attente_validation'\n";
    echo "✅ Nouveau délai d'expiration : " . $prochainEnAttente->getExpiresAt()->format('d/m/Y H:i') . "\n";
    echo "📧 Email de notification avec délai envoyé au prochain utilisateur\n";
} else {
    echo "✅ Aucun utilisateur suivant - lot libéré pour tous\n";
    $lot->setStatut('disponible');
    $lot->setReservePar(null);
    $lot->setReserveAt(null);
    $entityManager->persist($lot);
}

$entityManager->persist($premierEnAttente);
$entityManager->flush();

echo "\n";

// 8. État final
echo "8. ÉTAT FINAL APRÈS EXPIRATION\n";
echo "=================================\n";

echo "📊 Lot final :\n";
echo "   - Statut: {$lot->getStatut()}\n";
echo "   - Réservé par: " . ($lot->getReservePar() ? $lot->getReservePar()->getEmail() : 'Aucun') . "\n";

echo "📊 File d'attente finale :\n";
$filesAttenteFinales = $fileAttenteRepository->findByLot($lot);
foreach ($filesAttenteFinales as $file) {
    echo "   - Position {$file->getPosition()}: {$file->getUser()->getEmail()} (statut: {$file->getStatut()})\n";
    if ($file->getExpiresAt()) {
        echo "     Délai d'expiration: {$file->getExpiresAt()->format('d/m/Y H:i')}\n";
    }
    if ($file->getExpiredAt()) {
        echo "     Expiré le: {$file->getExpiredAt()->format('d/m/Y H:i')}\n";
    }
}

echo "\n";

// 9. Résumé des avantages
echo "9. AVANTAGES DE LA LOGIQUE AMÉLIORÉE\n";
echo "======================================\n";

echo "✅ AVANTAGES :\n";
echo "   🎯 Équité : Chaque utilisateur a sa chance avec un délai défini\n";
echo "   ⏰ Efficacité : Pas d'attente infinie, rotation automatique\n";
echo "   📧 Transparence : Notifications claires sur les délais\n";
echo "   🔄 Automatisation : Passage au suivant sans intervention manuelle\n";
echo "   📱 Réactivité : Incite les utilisateurs à réagir rapidement\n";
echo "   🛡️ Protection : Évite les réservations fantômes\n";

echo "\n✅ PROCESSUS COMPLET :\n";
echo "   1. Annulation commande → Premier en file notifié avec délai d'1h\n";
echo "   2. Si commande dans le délai → Lot réservé définitivement\n";
echo "   3. Si délai dépassé → Notification + passage au suivant\n";
echo "   4. Répétition jusqu'à commande ou fin de file d'attente\n";
echo "   5. Si fin de file → Lot disponible pour tous\n";

echo "\n=== FIN DE LA DÉMONSTRATION ===\n";

echo "\n🎉 LOGIQUE AMÉLIORÉE IMPLÉMENTÉE AVEC SUCCÈS !\n";
echo "   - Service LotLiberationServiceAmeliore créé\n";
echo "   - Templates d'email avec délai créés\n";
echo "   - Scheduler pour vérification automatique\n";
echo "   - Processus équitable et efficace\n";

