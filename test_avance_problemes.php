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

echo "=== TEST AVANCÉ : PROBLÈMES POTENTIELS ===\n\n";

// Test 1: Vérifier la logique de file d'attente avec plusieurs utilisateurs
echo "1. TEST AVEC PLUSIEURS UTILISATEURS EN FILE D'ATTENTE\n";
echo "====================================================\n";

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

// Trouver plusieurs utilisateurs
$users = $userRepository->createQueryBuilder('u')
    ->setMaxResults(4)
    ->getQuery()
    ->getResult();

if (count($users) < 3) {
    echo "❌ Pas assez d'utilisateurs pour le test\n";
    exit(1);
}

$user1 = $users[0];
$user2 = $users[1];
$user3 = $users[2];

echo "✅ Utilisateurs: {$user1->getEmail()}, {$user2->getEmail()}, {$user3->getEmail()}\n\n";

// Créer une commande pour user1
echo "2. CRÉATION COMMANDE USER1\n";
echo "===========================\n";

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

echo "✅ Commande créée et lot réservé\n\n";

// Ajouter user2 et user3 à la file d'attente
echo "3. AJOUT MULTIPLES À LA FILE D'ATTENTE\n";
echo "======================================\n";

// User2
$file2 = new FileAttente();
$file2->setLot($lot);
$file2->setUser($user2);
$file2->setPosition($fileAttenteRepository->getNextPosition($lot));
$entityManager->persist($file2);

// User3
$file3 = new FileAttente();
$file3->setLot($lot);
$file3->setUser($user3);
$file3->setPosition($fileAttenteRepository->getNextPosition($lot));
$entityManager->persist($file3);

$entityManager->flush();

echo "✅ User2 ajouté en position {$file2->getPosition()}\n";
echo "✅ User3 ajouté en position {$file3->getPosition()}\n";

// Vérifier la file d'attente
$filesAttente = $fileAttenteRepository->findByLot($lot);
echo "\n📋 File d'attente:\n";
foreach ($filesAttente as $file) {
    echo "   - Position {$file->getPosition()}: {$file->getUser()->getEmail()} (statut: {$file->getStatut()})\n";
}

echo "\n";

// Test 4: Annulation et vérification de la notification
echo "4. ANNULATION ET NOTIFICATION\n";
echo "==============================\n";

// Annuler la commande
$commande->setStatut('annulee');
$entityManager->persist($commande);

// Remettre le lot disponible
$lot->setStatut('disponible');
$lot->setReservePar(null);
$lot->setReserveAt(null);
$lot->setQuantite(1);
$entityManager->persist($lot);

// Notifier le premier en file d'attente
$filesAttente = $fileAttenteRepository->findByLot($lot);
if (!empty($filesAttente)) {
    $premierEnFile = $filesAttente[0];
    $premierEnFile->setStatut('notifie');
    $premierEnFile->setNotifiedAt(new \DateTimeImmutable());
    $entityManager->persist($premierEnFile);

    echo "✅ Premier utilisateur notifié: {$premierEnFile->getUser()->getEmail()}\n";
}

$entityManager->flush();

echo "\n";

// Test 5: Vérifier les problèmes potentiels
echo "5. VÉRIFICATION DES PROBLÈMES POTENTIELS\n";
echo "========================================\n";

// Problème 1: Vérifier si la file d'attente est correctement gérée
$filesAttenteFinales = $fileAttenteRepository->findByLot($lot);
echo "📊 File d'attente après annulation:\n";
if (empty($filesAttenteFinales)) {
    echo "   ⚠️  PROBLÈME: La file d'attente est vide après annulation\n";
    echo "   ⚠️  Les utilisateurs en attente devraient être notifiés mais rester en file\n";
} else {
    foreach ($filesAttenteFinales as $file) {
        echo "   - Position {$file->getPosition()}: {$file->getUser()->getEmail()} (statut: {$file->getStatut()})\n";
    }
}

// Problème 2: Vérifier la cohérence des positions
$positions = array_map(fn($f) => $f->getPosition(), $filesAttenteFinales);
$positionsUniques = array_unique($positions);
if (count($positions) !== count($positionsUniques)) {
    echo "\n⚠️  PROBLÈME: Positions dupliquées dans la file d'attente\n";
}

// Problème 3: Vérifier si le lot est vraiment disponible pour le premier en file
echo "\n📊 Vérification disponibilité pour le premier en file:\n";
if (!empty($filesAttenteFinales)) {
    $premier = $filesAttenteFinales[0];
    $estDisponible = $lot->isDisponiblePour($premier->getUser());
    echo "   - Premier en file: {$premier->getUser()->getEmail()}\n";
    echo "   - Peut-il commander: " . ($estDisponible ? "✅ OUI" : "❌ NON") . "\n";

    if (!$estDisponible) {
        echo "   ⚠️  PROBLÈME: Le premier en file ne peut pas commander le lot\n";
    }
}

// Problème 4: Vérifier la logique de suppression de la file d'attente
echo "\n📊 Logique de gestion de la file d'attente:\n";
echo "   - Après annulation, le premier utilisateur devrait pouvoir commander\n";
echo "   - Les autres utilisateurs devraient rester en file d'attente\n";
echo "   - Le système devrait gérer automatiquement les notifications\n";

echo "\n=== ANALYSE DES PROBLÈMES ===\n";

$problemesDetectes = [];

if (empty($filesAttenteFinales)) {
    $problemesDetectes[] = "La file d'attente est vidée après annulation au lieu de notifier les utilisateurs";
}

if (!empty($filesAttenteFinales)) {
    $premier = $filesAttenteFinales[0];
    if (!$lot->isDisponiblePour($premier->getUser())) {
        $problemesDetectes[] = "Le premier utilisateur en file d'attente ne peut pas commander le lot";
    }
}

if (empty($problemesDetectes)) {
    echo "✅ Aucun problème majeur détecté dans le système\n";
} else {
    echo "❌ Problèmes détectés:\n";
    foreach ($problemesDetectes as $probleme) {
        echo "   - {$probleme}\n";
    }
}

echo "\n=== FIN DU TEST AVANCÉ ===\n";

