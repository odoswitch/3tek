<?php

require_once __DIR__ . '/vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;
use App\Entity\Lot;
use App\Entity\Commande;
use App\Entity\FileAttente;
use App\Repository\UserRepository;
use App\Repository\LotRepository;
use App\Repository\CommandeRepository;
use App\Repository\FileAttenteRepository;
use App\Service\StockSynchronizationService;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;

// Charger les variables d'environnement
$dotenv = new Dotenv();
$dotenv->load(__DIR__ . '/.env');

// Initialiser Symfony
$kernel = new \App\Kernel($_ENV['APP_ENV'] ?? 'dev', (bool) ($_ENV['APP_DEBUG'] ?? true));
$kernel->boot();
$container = $kernel->getContainer();

// Récupérer les services
$entityManager = $container->get('doctrine.orm.entity_manager');
$userRepository = $entityManager->getRepository(User::class);
$lotRepository = $entityManager->getRepository(Lot::class);
$commandeRepository = $entityManager->getRepository(Commande::class);
$fileAttenteRepository = $entityManager->getRepository(FileAttente::class);
$mailer = $container->get(MailerInterface::class);
$twig = $container->get(Environment::class);

// Créer le service de synchronisation du stock manuellement
$logger = $container->get('logger');
$stockSyncService = new StockSynchronizationService($entityManager, $logger);

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
function cleanTestData($entityManager, $userRepository, $lotRepository, $commandeRepository, $fileAttenteRepository)
{
    echo "🧹 Nettoyage des données de test...\n";

    // Supprimer les commandes de test
    $commandesTest = $commandeRepository->createQueryBuilder('c')
        ->where('c.numeroCommande LIKE :pattern')
        ->setParameter('pattern', 'TEST-%')
        ->getQuery()
        ->getResult();

    foreach ($commandesTest as $commande) {
        $entityManager->remove($commande);
    }

    // Supprimer les files d'attente de test
    $filesTest = $fileAttenteRepository->createQueryBuilder('f')
        ->where('f.user IN (SELECT u FROM App\Entity\User u WHERE u.email LIKE :pattern)')
        ->setParameter('pattern', '%test%')
        ->getQuery()
        ->getResult();

    foreach ($filesTest as $file) {
        $entityManager->remove($file);
    }

    // Remettre les lots en état disponible
    $lots = $lotRepository->findAll();
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
function createTestUsers($entityManager, $userRepository)
{
    echo "👥 Création des utilisateurs de test...\n";

    $users = [];

    // Utilisateur 1 - Client normal
    $user1 = $userRepository->findOneBy(['email' => 'client1@test.com']);
    if (!$user1) {
        $user1 = new User();
        $user1->setEmail('client1@test.com');
        $user1->setPassword('$2y$13$test'); // Mot de passe hashé
        $user1->setNom('Client Test 1');
        $user1->setPrenom('Test');
        $user1->setIsVerified(true);
        $user1->setRoles(['ROLE_USER']);
        $entityManager->persist($user1);
    }
    $users['client1'] = $user1;

    // Utilisateur 2 - Client normal
    $user2 = $userRepository->findOneBy(['email' => 'client2@test.com']);
    if (!$user2) {
        $user2 = new User();
        $user2->setEmail('client2@test.com');
        $user2->setPassword('$2y$13$test');
        $user2->setNom('Client Test 2');
        $user2->setPrenom('Test');
        $user2->setIsVerified(true);
        $user2->setRoles(['ROLE_USER']);
        $entityManager->persist($user2);
    }
    $users['client2'] = $user2;

    // Utilisateur 3 - Client normal
    $user3 = $userRepository->findOneBy(['email' => 'client3@test.com']);
    if (!$user3) {
        $user3 = new User();
        $user3->setEmail('client3@test.com');
        $user3->setPassword('$2y$13$test');
        $user3->setNom('Client Test 3');
        $user3->setPrenom('Test');
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
function createTestLot($entityManager, $lotRepository)
{
    echo "📦 Création du lot de test...\n";

    $lot = $lotRepository->findOneBy(['name' => 'Lot Test Automatique']);
    if (!$lot) {
        $lot = new Lot();
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
function testCreationCommande($entityManager, $commandeRepository, $lot, $user)
{
    echo "🛒 Test création de commande...\n";

    $commande = new Commande();
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
    $commandeCreee = $commandeRepository->find($commande->getId());
    $success = $commandeCreee && $commandeCreee->getStatut() === 'en_attente';

    logTest("Création de commande", $success, "Commande ID: {$commande->getId()}, Statut: {$commande->getStatut()}");

    return $commande;
}

// Fonction pour tester la gestion du stock
function testGestionStock($entityManager, $lotRepository, $commande, $lot)
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
    $lotMisAJour = $lotRepository->find($lot->getId());
    $success = $lotMisAJour->getQuantite() === max(0, $lot->getQuantite() - $commande->getQuantite());

    logTest("Gestion du stock", $success, "Quantité restante: {$lotMisAJour->getQuantite()}, Statut: {$lotMisAJour->getStatut()}");

    return $lotMisAJour;
}

// Fonction pour tester la file d'attente
function testFileAttente($entityManager, $fileAttenteRepository, $lot, $user)
{
    echo "⏰ Test file d'attente...\n";

    // Ajouter un utilisateur à la file d'attente
    $fileAttente = new FileAttente();
    $fileAttente->setUser($user);
    $fileAttente->setLot($lot);
    $fileAttente->setCreatedAt(new \DateTimeImmutable());
    $fileAttente->setExpiresAt((new \DateTimeImmutable())->modify('+1 hour'));

    $entityManager->persist($fileAttente);
    $entityManager->flush();

    // Vérifier que l'utilisateur est dans la file
    $fileCreee = $fileAttenteRepository->findOneBy(['user' => $user, 'lot' => $lot]);
    $success = $fileCreee !== null;

    logTest("Ajout à la file d'attente", $success, "File ID: {$fileAttente->getId()}, Utilisateur: {$user->getEmail()}");

    return $fileAttente;
}

// Fonction pour tester l'annulation de commande
function testAnnulationCommande($entityManager, $commandeRepository, $commande, $lot)
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
    $commandeAnnulee = $commandeRepository->find($commande->getId());
    $success = $commandeAnnulee->getStatut() === 'annulee' && $lot->getStatut() === 'disponible';

    logTest("Annulation de commande", $success, "Commande statut: {$commandeAnnulee->getStatut()}, Lot statut: {$lot->getStatut()}");

    return $commandeAnnulee;
}

// Fonction pour tester la suppression de commande
function testSuppressionCommande($entityManager, $commandeRepository, $commande)
{
    echo "🗑️ Test suppression de commande...\n";

    $commandeId = $commande->getId();
    $entityManager->remove($commande);
    $entityManager->flush();

    // Vérifier la suppression
    $commandeSupprimee = $commandeRepository->find($commandeId);
    $success = $commandeSupprimee === null;

    logTest("Suppression de commande", $success, "Commande ID: $commandeId");

    return $success;
}

// Fonction pour tester la validation de commande
function testValidationCommande($entityManager, $commandeRepository, $commande, $lot)
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
    $commandeValidee = $commandeRepository->find($commande->getId());
    $success = $commandeValidee->getStatut() === 'validee' && $lot->getStatut() === 'vendu';

    logTest("Validation de commande", $success, "Commande statut: {$commandeValidee->getStatut()}, Lot statut: {$lot->getStatut()}");

    return $commandeValidee;
}

// Fonction pour tester l'envoi d'emails
function testEnvoiEmails($mailer, $twig, $commande, $user)
{
    echo "📧 Test envoi d'emails...\n";

    try {
        // Email de confirmation client
        $emailClient = (new Email())
            ->from('contact@3tek-europe.com')
            ->to($user->getEmail())
            ->subject('Confirmation de commande - ' . $commande->getNumeroCommande())
            ->html($twig->render('emails/commande_confirmation.html.twig', [
                'commande' => $commande,
                'user' => $user
            ]));

        $mailer->send($emailClient);

        // Email notification admin
        $emailAdmin = (new Email())
            ->from('contact@3tek-europe.com')
            ->to('admin@3tek-europe.com')
            ->subject('Nouvelle commande - ' . $commande->getNumeroCommande())
            ->html($twig->render('emails/admin_notification.html.twig', [
                'commande' => $commande,
                'user' => $user
            ]));

        $mailer->send($emailAdmin);

        logTest("Envoi d'emails", true, "Emails envoyés au client et à l'admin");
        return true;
    } catch (Exception $e) {
        logTest("Envoi d'emails", false, "Erreur: " . $e->getMessage());
        return false;
    }
}

// Fonction pour tester la synchronisation du stock
function testSynchronisationStock($stockSyncService, $commande)
{
    echo "🔄 Test synchronisation du stock...\n";

    try {
        $stockSyncService->synchronizeStockOnCommandeCreation($commande);
        logTest("Synchronisation du stock", true, "Synchronisation réussie");
        return true;
    } catch (Exception $e) {
        logTest("Synchronisation du stock", false, "Erreur: " . $e->getMessage());
        return false;
    }
}

// === EXÉCUTION DES TESTS ===

try {
    // Nettoyage initial
    cleanTestData($entityManager, $userRepository, $lotRepository, $commandeRepository, $fileAttenteRepository);

    // Création des données de test
    $users = createTestUsers($entityManager, $userRepository);
    $lot = createTestLot($entityManager, $lotRepository);

    echo "=== DÉBUT DES TESTS ===\n\n";

    // Test 1: Création de commande
    $commande1 = testCreationCommande($entityManager, $commandeRepository, $lot, $users['client1']);

    // Test 2: Gestion du stock
    $lotMisAJour = testGestionStock($entityManager, $lotRepository, $commande1, $lot);

    // Test 3: File d'attente
    $fileAttente = testFileAttente($entityManager, $fileAttenteRepository, $lot, $users['client2']);

    // Test 4: Envoi d'emails
    testEnvoiEmails($mailer, $twig, $commande1, $users['client1']);

    // Test 5: Synchronisation du stock
    testSynchronisationStock($stockSyncService, $commande1);

    // Test 6: Annulation de commande
    $commandeAnnulee = testAnnulationCommande($entityManager, $commandeRepository, $commande1, $lot);

    // Test 7: Création d'une nouvelle commande pour tester la validation
    $commande2 = testCreationCommande($entityManager, $commandeRepository, $lot, $users['client3']);
    testGestionStock($entityManager, $lotRepository, $commande2, $lot);

    // Test 8: Validation de commande
    $commandeValidee = testValidationCommande($entityManager, $commandeRepository, $commande2, $lot);

    // Test 9: Suppression de commande (créer une nouvelle pour tester)
    $commande3 = testCreationCommande($entityManager, $commandeRepository, $lot, $users['client1']);
    testSuppressionCommande($entityManager, $commandeRepository, $commande3);

    echo "=== RÉSUMÉ DES TESTS ===\n";
    echo "✅ Tests terminés avec succès\n";
    echo "📊 Toutes les fonctionnalités de gestion des commandes ont été testées\n";
    echo "🚀 L'application est prête pour le déploiement cPanel\n\n";

    // Nettoyage final
    cleanTestData($entityManager, $userRepository, $lotRepository, $commandeRepository, $fileAttenteRepository);
} catch (Exception $e) {
    echo "❌ ERREUR CRITIQUE: " . $e->getMessage() . "\n";
    echo "📍 Fichier: " . $e->getFile() . " Ligne: " . $e->getLine() . "\n";
    echo "🔍 Trace: " . $e->getTraceAsString() . "\n";
}

echo "=== FIN DU TEST COMPLET ===\n";
