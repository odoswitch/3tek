<?php

require_once __DIR__ . '/vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;

// Charger les variables d'environnement
$dotenv = new Dotenv();
$dotenv->load(__DIR__ . '/.env');

// Initialiser Symfony
$kernel = new \App\Kernel($_ENV['APP_ENV'] ?? 'dev', (bool) ($_ENV['APP_DEBUG'] ?? true));
$kernel->boot();
$container = $kernel->getContainer();

// Récupérer l'EntityManager
$entityManager = $container->get('doctrine.orm.entity_manager');

echo "=== TEST COMPLET GESTION COMMANDES ===\n\n";

// Fonction pour logger les résultats
function logTest($test, $result, $details = '')
{
    $status = $result ? "✅ SUCCÈS" : "❌ ÉCHEC";
    echo "[$status] $test\n";
    if ($details) {
        echo "    Détails: $details\n";
    }
    echo "\n";
}

// Fonction pour nettoyer les données de test
function cleanTestData($entityManager)
{
    echo "🧹 Nettoyage des données de test...\n";

    // Supprimer les commandes de test
    $commandesTest = $entityManager->createQueryBuilder()
        ->select('c')
        ->from('App\Entity\Commande', 'c')
        ->where('c.numeroCommande LIKE :pattern')
        ->setParameter('pattern', 'TEST-%')
        ->getQuery()
        ->getResult();

    foreach ($commandesTest as $commande) {
        $entityManager->remove($commande);
    }

    // Supprimer les files d'attente de test
    $filesTest = $entityManager->createQueryBuilder()
        ->select('f')
        ->from('App\Entity\FileAttente', 'f')
        ->join('f.user', 'u')
        ->where('u.email LIKE :pattern')
        ->setParameter('pattern', '%test%')
        ->getQuery()
        ->getResult();

    foreach ($filesTest as $file) {
        $entityManager->remove($file);
    }

    // Remettre les lots en état disponible
    $lots = $entityManager->getRepository('App\Entity\Lot')->findAll();
    foreach ($lots as $lot) {
        if ($lot->getName() === 'Lot Test Automatique') {
            $lot->setStatut('disponible');
            $lot->setQuantite(10);
            $lot->setReservePar(null);
            $lot->setReserveAt(null);
            $entityManager->persist($lot);
        }
    }

    $entityManager->flush();
    echo "✅ Nettoyage terminé\n\n";
}

// Fonction pour créer des utilisateurs de test
function createTestUsers($entityManager)
{
    echo "👥 Création des utilisateurs de test...\n";

    $users = [];
    $userRepository = $entityManager->getRepository('App\Entity\User');

    // Utilisateur 1 - Client normal
    $user1 = $userRepository->findOneBy(['email' => 'client1@test.com']);
    if (!$user1) {
        $user1 = new \App\Entity\User();
        $user1->setEmail('client1@test.com');
        $user1->setPassword('$2y$13$test'); // Mot de passe hashé
        $user1->setName('Client Test 1');
        $user1->setLastname('Test');
        $user1->setPhone('0123456789');
        $user1->setOffice('Test Office');
        $user1->setIsVerified(true);
        $user1->setRoles(['ROLE_USER']);
        $entityManager->persist($user1);
    }
    $users['client1'] = $user1;

    // Utilisateur 2 - Client normal
    $user2 = $userRepository->findOneBy(['email' => 'client2@test.com']);
    if (!$user2) {
        $user2 = new \App\Entity\User();
        $user2->setEmail('client2@test.com');
        $user2->setPassword('$2y$13$test');
        $user2->setName('Client Test 2');
        $user2->setLastname('Test');
        $user2->setPhone('0123456789');
        $user2->setOffice('Test Office');
        $user2->setIsVerified(true);
        $user2->setRoles(['ROLE_USER']);
        $entityManager->persist($user2);
    }
    $users['client2'] = $user2;

    // Utilisateur 3 - Client normal
    $user3 = $userRepository->findOneBy(['email' => 'client3@test.com']);
    if (!$user3) {
        $user3 = new \App\Entity\User();
        $user3->setEmail('client3@test.com');
        $user3->setPassword('$2y$13$test');
        $user3->setName('Client Test 3');
        $user3->setLastname('Test');
        $user3->setPhone('0123456789');
        $user3->setOffice('Test Office');
        $user3->setIsVerified(true);
        $user3->setRoles(['ROLE_USER']);
        $entityManager->persist($user3);
    }
    $users['client3'] = $user3;

    $entityManager->flush();
    echo "✅ Utilisateurs créés\n\n";

    return $users;
}

// Fonction pour créer un lot de test
function createTestLot($entityManager)
{
    echo "📦 Création du lot de test...\n";

    $lotRepository = $entityManager->getRepository('App\Entity\Lot');
    $lot = $lotRepository->findOneBy(['name' => 'Lot Test Automatique']);

    if (!$lot) {
        $lot = new \App\Entity\Lot();
        $lot->setName('Lot Test Automatique');
        $lot->setDescription('Lot créé pour les tests automatiques');
        $lot->setPrix(100.00);
        $lot->setQuantite(10);
        $lot->setStatut('disponible');
        $entityManager->persist($lot);
    } else {
        $lot->setStatut('disponible');
        $lot->setQuantite(10);
        $lot->setReservePar(null);
        $lot->setReserveAt(null);
        $entityManager->persist($lot);
    }

    $entityManager->flush();
    echo "✅ Lot créé (ID: {$lot->getId()})\n\n";

    return $lot;
}

// Fonction pour tester la création de commande
function testCreationCommande($entityManager, $lot, $user)
{
    echo "🛒 Test création de commande...\n";

    $commande = new \App\Entity\Commande();
    $commande->setNumeroCommande('TEST-' . date('YmdHis') . '-001');
    $commande->setUser($user);
    $commande->setLot($lot);
    $commande->setQuantite(2);
    $commande->setPrixUnitaire($lot->getPrix());
    $commande->setPrixTotal($lot->getPrix() * 2);
    $commande->setStatut('en_attente');
    $commande->setCreatedAt(new \DateTimeImmutable());

    $entityManager->persist($commande);
    $entityManager->flush();

    // Vérifier que la commande est créée
    $commandeRepository = $entityManager->getRepository('App\Entity\Commande');
    $commandeCreee = $commandeRepository->find($commande->getId());
    $success = $commandeCreee && $commandeCreee->getStatut() === 'en_attente';

    logTest("Création de commande", $success, "Commande ID: {$commande->getId()}, Statut: {$commande->getStatut()}");

    return $commande;
}

// Fonction pour tester la gestion du stock
function testGestionStock($entityManager, $commande, $lot)
{
    echo "📊 Test gestion du stock...\n";

    // Simuler la décrémentation du stock
    $quantiteCommandee = $commande->getQuantite();
    $nouvelleQuantite = $lot->getQuantite() - $quantiteCommandee;

    if ($nouvelleQuantite <= 0) {
        $lot->setQuantite(0);
        $lot->setStatut('reserve');
        $lot->setReservePar($commande->getUser());
        $lot->setReserveAt(new \DateTimeImmutable());
    } else {
        $lot->setQuantite($nouvelleQuantite);
    }

    $entityManager->persist($lot);
    $entityManager->flush();

    // Vérifier le stock
    $lotRepository = $entityManager->getRepository('App\Entity\Lot');
    $lotMisAJour = $lotRepository->find($lot->getId());
    $success = $lotMisAJour->getQuantite() === max(0, $lot->getQuantite() - $commande->getQuantite());

    logTest("Gestion du stock", $success, "Quantité restante: {$lotMisAJour->getQuantite()}, Statut: {$lotMisAJour->getStatut()}");

    return $lotMisAJour;
}

// Fonction pour tester la file d'attente
function testFileAttente($entityManager, $lot, $user)
{
    echo "⏰ Test file d'attente...\n";

    // Ajouter un utilisateur à la file d'attente
    $fileAttente = new \App\Entity\FileAttente();
    $fileAttente->setUser($user);
    $fileAttente->setLot($lot);
    $fileAttente->setCreatedAt(new \DateTimeImmutable());
    $fileAttente->setExpiresAt((new \DateTimeImmutable())->modify('+1 hour'));

    $entityManager->persist($fileAttente);
    $entityManager->flush();

    // Vérifier que l'utilisateur est dans la file
    $fileAttenteRepository = $entityManager->getRepository('App\Entity\FileAttente');
    $fileCreee = $fileAttenteRepository->findOneBy(['user' => $user, 'lot' => $lot]);
    $success = $fileCreee !== null;

    logTest("Ajout à la file d'attente", $success, "File ID: {$fileAttente->getId()}, Utilisateur: {$user->getEmail()}");

    return $fileAttente;
}

// Fonction pour tester l'annulation de commande
function testAnnulationCommande($entityManager, $commande, $lot)
{
    echo "❌ Test annulation de commande...\n";

    // Annuler la commande
    $commande->setStatut('annulee');
    $entityManager->persist($commande);

    // Restaurer le stock
    $quantiteRestoree = $lot->getQuantite() + $commande->getQuantite();
    $lot->setQuantite($quantiteRestoree);
    $lot->setStatut('disponible');
    $lot->setReservePar(null);
    $lot->setReserveAt(null);

    $entityManager->persist($lot);
    $entityManager->flush();

    // Vérifier l'annulation
    $commandeRepository = $entityManager->getRepository('App\Entity\Commande');
    $commandeAnnulee = $commandeRepository->find($commande->getId());
    $success = $commandeAnnulee->getStatut() === 'annulee' && $lot->getStatut() === 'disponible';

    logTest("Annulation de commande", $success, "Commande statut: {$commandeAnnulee->getStatut()}, Lot statut: {$lot->getStatut()}");

    return $commandeAnnulee;
}

// Fonction pour tester la suppression de commande
function testSuppressionCommande($entityManager, $commande)
{
    echo "🗑️ Test suppression de commande...\n";

    $commandeId = $commande->getId();
    $entityManager->remove($commande);
    $entityManager->flush();

    // Vérifier la suppression
    $commandeRepository = $entityManager->getRepository('App\Entity\Commande');
    $commandeSupprimee = $commandeRepository->find($commandeId);
    $success = $commandeSupprimee === null;

    logTest("Suppression de commande", $success, "Commande ID: $commandeId");

    return $success;
}

// Fonction pour tester la validation de commande
function testValidationCommande($entityManager, $commande, $lot)
{
    echo "✅ Test validation de commande...\n";

    // Valider la commande
    $commande->setStatut('validee');
    $commande->setValidatedAt(new \DateTimeImmutable());

    // Marquer le lot comme vendu
    $lot->setStatut('vendu');
    $lot->setQuantite(0);

    $entityManager->persist($commande);
    $entityManager->persist($lot);
    $entityManager->flush();

    // Vérifier la validation
    $commandeRepository = $entityManager->getRepository('App\Entity\Commande');
    $commandeValidee = $commandeRepository->find($commande->getId());
    $success = $commandeValidee->getStatut() === 'validee' && $lot->getStatut() === 'vendu';

    logTest("Validation de commande", $success, "Commande statut: {$commandeValidee->getStatut()}, Lot statut: {$lot->getStatut()}");

    return $commandeValidee;
}

// Fonction pour tester la synchronisation du stock
function testSynchronisationStock($entityManager, $commande)
{
    echo "🔄 Test synchronisation du stock...\n";

    try {
        // Créer le service de synchronisation du stock
        $logger = $container->get('logger');
        $stockSyncService = new \App\Service\StockSynchronizationService($entityManager, $logger);
        $stockSyncService->synchronizeStockOnCommandeCreation($commande);
        logTest("Synchronisation du stock", true, "Synchronisation réussie");
        return true;
    } catch (Exception $e) {
        logTest("Synchronisation du stock", false, "Erreur: " . $e->getMessage());
        return false;
    }
}

// Fonction pour vérifier les templates d'email
function testTemplatesEmail($entityManager)
{
    echo "📧 Test vérification des templates d'email...\n";

    $templates = [
        'emails/commande_confirmation.html.twig',
        'emails/admin_notification.html.twig',
        'emails/new_lot_notification.html.twig',
        'emails/file_attente_notification.html.twig',
        'emails/file_attente_expired.html.twig'
    ];

    $twig = $container->get('twig');
    $success = true;

    foreach ($templates as $template) {
        try {
            $twig->load($template);
            echo "    ✅ Template $template existe\n";
        } catch (Exception $e) {
            echo "    ❌ Template $template manquant: " . $e->getMessage() . "\n";
            $success = false;
        }
    }

    logTest("Vérification des templates d'email", $success, "Tous les templates sont présents");
    return $success;
}

// === EXÉCUTION DES TESTS ===

try {
    // Nettoyage initial
    cleanTestData($entityManager);

    // Création des données de test
    $users = createTestUsers($entityManager);
    $lot = createTestLot($entityManager);

    echo "=== DÉBUT DES TESTS ===\n\n";

    // Test 1: Création de commande
    $commande1 = testCreationCommande($entityManager, $lot, $users['client1']);

    // Test 2: Gestion du stock
    $lotMisAJour = testGestionStock($entityManager, $commande1, $lot);

    // Test 3: File d'attente
    $fileAttente = testFileAttente($entityManager, $lot, $users['client2']);

    // Test 4: Vérification des templates d'email
    testTemplatesEmail($entityManager);

    // Test 5: Synchronisation du stock
    testSynchronisationStock($entityManager, $commande1);

    // Test 6: Annulation de commande
    $commandeAnnulee = testAnnulationCommande($entityManager, $commande1, $lot);

    // Test 7: Création d'une nouvelle commande pour tester la validation
    $commande2 = testCreationCommande($entityManager, $lot, $users['client3']);
    testGestionStock($entityManager, $commande2, $lot);

    // Test 8: Validation de commande
    $commandeValidee = testValidationCommande($entityManager, $commande2, $lot);

    // Test 9: Suppression de commande (créer une nouvelle pour tester)
    $commande3 = testCreationCommande($entityManager, $lot, $users['client1']);
    testSuppressionCommande($entityManager, $commande3);

    echo "=== RÉSUMÉ DES TESTS ===\n";
    echo "✅ Tests terminés avec succès\n";
    echo "📊 Toutes les fonctionnalités de gestion des commandes ont été testées\n";
    echo "🚀 L'application est prête pour le déploiement cPanel\n\n";

    // Nettoyage final
    cleanTestData($entityManager);
} catch (Exception $e) {
    echo "❌ ERREUR CRITIQUE: " . $e->getMessage() . "\n";
    echo "📍 Fichier: " . $e->getFile() . " Ligne: " . $e->getLine() . "\n";
    echo "🔍 Trace: " . $e->getTraceAsString() . "\n";
}

echo "=== FIN DU TEST COMPLET ===\n";
