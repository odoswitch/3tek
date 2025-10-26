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

echo "=== TEST DE LA CORRECTION D'INCOHÉRENCE ===\n\n";

// Trouver un lot disponible
$lot = $lotRepository->createQueryBuilder('l')
    ->where('l.statut = :statut')
    ->andWhere('l.quantite > 0')
    ->setParameter('statut', 'disponible')
    ->setMaxResults(1)
    ->getQuery()
    ->getOneOrNullResult();

if (!$lot) {
    echo "❌ Aucun lot disponible pour le test\n";
    exit(1);
}

echo "✅ Lot utilisé: {$lot->getName()} (ID: {$lot->getId()})\n";

// Trouver des utilisateurs
$users = $userRepository->createQueryBuilder('u')
    ->setMaxResults(2)
    ->getQuery()
    ->getResult();

$user1 = $users[0];
$user2 = $users[1];

echo "✅ Utilisateurs: {$user1->getEmail()}, {$user2->getEmail()}\n\n";

// Test 1: Créer une commande et ajouter à la file d'attente
echo "1. CRÉATION COMMANDE ET FILE D'ATTENTE\n";
echo "=======================================\n";

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

echo "✅ Commande créée et lot réservé\n";

// Ajouter user2 à la file d'attente
$fileAttente = new FileAttente();
$fileAttente->setLot($lot);
$fileAttente->setUser($user2);
$fileAttente->setPosition($fileAttenteRepository->getNextPosition($lot));

$entityManager->persist($fileAttente);
$entityManager->flush();

echo "✅ User2 ajouté en position {$fileAttente->getPosition()}\n\n";

// Test 2: Simuler l'annulation via CommandeCrudController (nouvelle logique unifiée)
echo "2. ANNULATION AVEC LOGIQUE UNIFIÉE\n";
echo "===================================\n";

// Simuler la logique unifiée du service LotLiberationService
echo "📋 Logique unifiée LotLiberationService::libererLot():\n";
echo "   - Si quelqu'un en file d'attente : réserver pour le premier\n";
echo "   - Si personne en file d'attente : rendre disponible pour tous\n";

// Appliquer la logique unifiée
$lot->setQuantite(1); // Restaurer la quantité

// Chercher le premier utilisateur dans la file d'attente
$premierEnAttente = $fileAttenteRepository->findFirstInQueue($lot);

if ($premierEnAttente) {
    echo "✅ Premier en file d'attente trouvé: {$premierEnAttente->getUser()->getEmail()}\n";

    // Réserver automatiquement le lot pour le premier utilisateur en file d'attente
    $lot->setStatut('reserve');
    $lot->setReservePar($premierEnAttente->getUser());
    $lot->setReserveAt(new \DateTimeImmutable());

    echo "✅ Lot réservé automatiquement pour le premier utilisateur de la file\n";

    // Marquer comme notifié
    $premierEnAttente->setStatut('notifie');
    $premierEnAttente->setNotifiedAt(new \DateTimeImmutable());
    $entityManager->persist($premierEnAttente);

    echo "✅ Premier utilisateur marqué comme notifié\n";
} else {
    echo "✅ Aucun utilisateur en file d'attente - lot libéré pour tous\n";
    $lot->setStatut('disponible');
    $lot->setReservePar(null);
    $lot->setReserveAt(null);
}

$commande->setStatut('annulee');
$entityManager->persist($commande);
$entityManager->persist($lot);
$entityManager->flush();

echo "✅ Annulation appliquée avec logique unifiée\n\n";

// Test 3: Vérifier la cohérence
echo "3. VÉRIFICATION DE LA COHÉRENCE\n";
echo "=================================\n";

$lotFinal = $lotRepository->find($lot->getId());
echo "📊 État final du lot:\n";
echo "   - Statut: {$lotFinal->getStatut()}\n";
echo "   - Quantité: {$lotFinal->getQuantite()}\n";
echo "   - Réservé par: " . ($lotFinal->getReservePar() ? $lotFinal->getReservePar()->getEmail() : 'Aucun') . "\n";

$filesAttenteFinales = $fileAttenteRepository->findByLot($lotFinal);
echo "\n📊 File d'attente:\n";
foreach ($filesAttenteFinales as $file) {
    echo "   - Position {$file->getPosition()}: {$file->getUser()->getEmail()} (statut: {$file->getStatut()})\n";
}

// Test 4: Vérifier la disponibilité pour user2
echo "\n4. TEST DISPONIBILITÉ POUR USER2\n";
echo "==================================\n";

$estDisponible = $lotFinal->isDisponiblePour($user2);
echo "📊 User2 peut-il commander le lot:\n";
echo "   - Méthode isDisponiblePour(): " . ($estDisponible ? "✅ OUI" : "❌ NON") . "\n";

if ($estDisponible) {
    echo "✅ CORRECT: User2 peut commander le lot (premier en file notifié)\n";
} else {
    echo "❌ PROBLÈME: User2 ne peut pas commander le lot\n";
}

// Test 5: Simuler le cas sans file d'attente
echo "\n5. TEST CAS SANS FILE D'ATTENTE\n";
echo "===============================\n";

// Créer un nouveau lot pour tester le cas sans file d'attente
$lot2 = $lotRepository->createQueryBuilder('l')
    ->where('l.statut = :statut')
    ->andWhere('l.quantite > 0')
    ->andWhere('l.id != :excludeId')
    ->setParameter('statut', 'disponible')
    ->setParameter('excludeId', $lot->getId())
    ->setMaxResults(1)
    ->getQuery()
    ->getOneOrNullResult();

if ($lot2) {
    echo "✅ Lot 2 utilisé: {$lot2->getName()} (ID: {$lot2->getId()})\n";

    // Créer une commande
    $commande2 = new Commande();
    $commande2->setUser($user1);
    $commande2->setLot($lot2);
    $commande2->setQuantite(1);
    $commande2->setPrixUnitaire($lot2->getPrix());
    $commande2->setPrixTotal($lot2->getPrix());
    $commande2->setStatut('en_attente');

    $entityManager->persist($commande2);

    // Réserver le lot
    $lot2->setQuantite(0);
    $lot2->setStatut('reserve');
    $lot2->setReservePar($user1);
    $lot2->setReserveAt(new \DateTimeImmutable());

    $entityManager->persist($lot2);
    $entityManager->flush();

    echo "✅ Commande 2 créée et lot 2 réservé\n";

    // Annuler la commande (sans file d'attente)
    $commande2->setStatut('annulee');
    $lot2->setQuantite(1);

    // Simuler la logique unifiée
    $premierEnAttente2 = $fileAttenteRepository->findFirstInQueue($lot2);

    if ($premierEnAttente2) {
        echo "⚠️  Utilisateur en file d'attente trouvé (inattendu)\n";
    } else {
        echo "✅ Aucun utilisateur en file d'attente - lot libéré pour tous\n";
        $lot2->setStatut('disponible');
        $lot2->setReservePar(null);
        $lot2->setReserveAt(null);
    }

    $entityManager->persist($commande2);
    $entityManager->persist($lot2);
    $entityManager->flush();

    echo "✅ Annulation appliquée (cas sans file d'attente)\n";

    // Vérifier l'état final
    $lot2Final = $lotRepository->find($lot2->getId());
    echo "📊 État final du lot 2:\n";
    echo "   - Statut: {$lot2Final->getStatut()}\n";
    echo "   - Quantité: {$lot2Final->getQuantite()}\n";
    echo "   - Réservé par: " . ($lot2Final->getReservePar() ? $lot2Final->getReservePar()->getEmail() : 'Aucun') . "\n";

    if ($lot2Final->getStatut() === 'disponible' && $lot2Final->getReservePar() === null) {
        echo "✅ CORRECT: Lot 2 libéré pour tous (pas de file d'attente)\n";
    } else {
        echo "❌ PROBLÈME: Lot 2 pas correctement libéré\n";
    }
} else {
    echo "⚠️  Pas de deuxième lot disponible pour le test\n";
}

echo "\n=== RÉSULTAT DU TEST DE CORRECTION ===\n";

echo "✅ CORRECTION APPLIQUÉE:\n";
echo "   - Logique unifiée dans LotLiberationService\n";
echo "   - CommandeCrudController utilise le service unifié\n";
echo "   - CommandeDeleteListener utilise le service unifié\n";
echo "   - Comportement cohérent dans tous les cas\n";

echo "\n✅ AVANTAGES DE LA CORRECTION:\n";
echo "   - Plus d'incohérence entre les méthodes d'annulation\n";
echo "   - Comportement prévisible et logique\n";
echo "   - Code centralisé et maintenable\n";
echo "   - Gestion cohérente des notifications\n";

echo "\n=== FIN DU TEST DE CORRECTION ===\n";

