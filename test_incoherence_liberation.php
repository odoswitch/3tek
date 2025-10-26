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

echo "=== TEST SPÉCIFIQUE : INCOHÉRENCE DE LIBÉRATION ===\n\n";

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

// Créer une commande
echo "1. CRÉATION COMMANDE\n";
echo "====================\n";

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

// Ajouter user2 à la file d'attente
echo "2. AJOUT À LA FILE D'ATTENTE\n";
echo "=============================\n";

$fileAttente = new FileAttente();
$fileAttente->setLot($lot);
$fileAttente->setUser($user2);
$fileAttente->setPosition($fileAttenteRepository->getNextPosition($lot));

$entityManager->persist($fileAttente);
$entityManager->flush();

echo "✅ User2 ajouté en position {$fileAttente->getPosition()}\n\n";

// Test 3: Simuler l'annulation via CommandeCrudController (méthode updateEntity)
echo "3. ANNULATION VIA COMMANDECRUDCONTROLLER\n";
echo "=========================================\n";

// Simuler la logique de CommandeCrudController::libererLot()
echo "📋 Logique CommandeCrudController::libererLot():\n";
echo "   - Met le lot en statut 'disponible'\n";
echo "   - Remet reservePar à null\n";
echo "   - Restaure la quantité\n";
echo "   - Notifie le premier en file d'attente\n";

// Appliquer cette logique
$lot->setStatut('disponible');
$lot->setReservePar(null);
$lot->setReserveAt(null);
$lot->setQuantite(1);

// Notifier le premier en file d'attente
$filesAttente = $fileAttenteRepository->findByLot($lot);
if (!empty($filesAttente)) {
    $premierEnFile = $filesAttente[0];
    $premierEnFile->setStatut('notifie');
    $premierEnFile->setNotifiedAt(new \DateTimeImmutable());
    $entityManager->persist($premierEnFile);

    echo "✅ Premier utilisateur notifié: {$premierEnFile->getUser()->getEmail()}\n";
}

$commande->setStatut('annulee');
$entityManager->persist($commande);
$entityManager->persist($lot);
$entityManager->flush();

echo "✅ Annulation appliquée (logique CommandeCrudController)\n\n";

// Vérifier l'état
echo "4. VÉRIFICATION ÉTAT APRÈS ANNULATION\n";
echo "======================================\n";

$lotFinal = $lotRepository->find($lot->getId());
echo "📊 État du lot:\n";
echo "   - Statut: {$lotFinal->getStatut()}\n";
echo "   - Quantité: {$lotFinal->getQuantite()}\n";
echo "   - Réservé par: " . ($lotFinal->getReservePar() ? $lotFinal->getReservePar()->getEmail() : 'Aucun') . "\n";

$filesAttenteFinales = $fileAttenteRepository->findByLot($lotFinal);
echo "\n📊 File d'attente:\n";
foreach ($filesAttenteFinales as $file) {
    echo "   - Position {$file->getPosition()}: {$file->getUser()->getEmail()} (statut: {$file->getStatut()})\n";
}

// Test 5: Vérifier la disponibilité pour user2
echo "\n5. TEST DISPONIBILITÉ POUR USER2\n";
echo "==================================\n";

$estDisponible = $lotFinal->isDisponiblePour($user2);
echo "📊 User2 peut-il commander le lot:\n";
echo "   - Méthode isDisponiblePour(): " . ($estDisponible ? "✅ OUI" : "❌ NON") . "\n";
echo "   - Statut du lot: {$lotFinal->getStatut()}\n";
echo "   - Quantité du lot: {$lotFinal->getQuantite()}\n";

if ($estDisponible) {
    echo "✅ CORRECT: User2 peut commander le lot\n";
} else {
    echo "❌ PROBLÈME: User2 ne peut pas commander le lot malgré la notification\n";
}

// Test 6: Simuler l'annulation via CommandeDeleteListener
echo "\n6. SIMULATION COMMANDEDELETELISTENER\n";
echo "=====================================\n";

echo "📋 Logique CommandeDeleteListener::libererLot():\n";
echo "   - Si quelqu'un en file d'attente: garde le lot 'reserve' pour le premier\n";
echo "   - Si personne en file d'attente: met le lot 'disponible'\n";

// Remettre le lot en état réservé pour tester
$lot->setStatut('reserve');
$lot->setReservePar($user1);
$lot->setReserveAt(new \DateTimeImmutable());
$entityManager->persist($lot);
$entityManager->flush();

// Simuler la logique du listener
$premierEnAttente = $fileAttenteRepository->findByLot($lot);
if (!empty($premierEnAttente)) {
    $premier = $premierEnAttente[0];
    echo "✅ Premier en file d'attente trouvé: {$premier->getUser()->getEmail()}\n";

    // Logique du listener: garder le lot réservé pour le premier
    $lot->setStatut('reserve');
    $lot->setReservePar($premier->getUser());
    $lot->setReserveAt(new \DateTimeImmutable());

    echo "✅ Lot gardé en statut 'reserve' pour le premier utilisateur\n";
} else {
    echo "✅ Aucun utilisateur en file d'attente - lot libéré\n";
    $lot->setStatut('disponible');
    $lot->setReservePar(null);
    $lot->setReserveAt(null);
}

$entityManager->persist($lot);
$entityManager->flush();

echo "\n7. COMPARAISON DES DEUX APPROCHES\n";
echo "==================================\n";

echo "📊 CommandeCrudController:\n";
echo "   - Met le lot 'disponible' pour tous\n";
echo "   - Notifie le premier en file d'attente\n";
echo "   - Le premier peut commander immédiatement\n";

echo "\n📊 CommandeDeleteListener:\n";
echo "   - Garde le lot 'reserve' pour le premier en file\n";
echo "   - Les autres utilisateurs ne voient pas le lot\n";
echo "   - Le premier doit confirmer sa commande\n";

echo "\n=== ANALYSE DU PROBLÈME ===\n";

echo "❌ INCOHÉRENCE DÉTECTÉE:\n";
echo "   - Deux logiques différentes pour la même action\n";
echo "   - CommandeCrudController: lot 'disponible' pour tous\n";
echo "   - CommandeDeleteListener: lot 'reserve' pour le premier\n";
echo "   - Cela peut créer de la confusion dans l'interface utilisateur\n";

echo "\n✅ RECOMMANDATION:\n";
echo "   - Unifier la logique de libération des lots\n";
echo "   - Choisir une approche cohérente\n";
echo "   - Documenter clairement le comportement attendu\n";

echo "\n=== FIN DU TEST SPÉCIFIQUE ===\n";

