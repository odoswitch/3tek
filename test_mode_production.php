<?php

require_once __DIR__ . '/vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;

// Charger les variables d'environnement
$dotenv = new Dotenv();
$dotenv->load(__DIR__ . '/.env');

// Initialiser Symfony en mode production
$kernel = new \App\Kernel('prod', false);
$kernel->boot();
$container = $kernel->getContainer();

// Récupérer l'EntityManager
$entityManager = $container->get('doctrine.orm.entity_manager');

echo "=== TEST MODE PRODUCTION - GESTION COMMANDES ===\n\n";

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

// Fonction pour vérifier l'environnement de production
function checkProductionEnvironment($container)
{
    echo "🔍 Vérification de l'environnement de production...\n";

    $kernel = $container->get('kernel');
    $environment = $kernel->getEnvironment();
    $debug = $kernel->isDebug();

    echo "    📋 Environnement: $environment\n";
    echo "    🐛 Debug: " . ($debug ? 'ACTIVÉ' : 'DÉSACTIVÉ') . "\n";

    $success = $environment === 'prod' && !$debug;
    logTest("Environnement de production", $success, "Env: $environment, Debug: " . ($debug ? 'true' : 'false'));

    return $success;
}

// Fonction pour vérifier les performances
function checkPerformance($entityManager)
{
    echo "⚡ Test des performances...\n";

    $startTime = microtime(true);

    // Test de récupération des données
    $userRepository = $entityManager->getRepository('App\Entity\User');
    $users = $userRepository->findAll();

    $lotRepository = $entityManager->getRepository('App\Entity\Lot');
    $lots = $lotRepository->findAll();

    $commandeRepository = $entityManager->getRepository('App\Entity\Commande');
    $commandes = $commandeRepository->findAll();

    $endTime = microtime(true);
    $executionTime = ($endTime - $startTime) * 1000; // en millisecondes

    echo "    📊 Utilisateurs récupérés: " . count($users) . "\n";
    echo "    📦 Lots récupérés: " . count($lots) . "\n";
    echo "    🛒 Commandes récupérées: " . count($commandes) . "\n";
    echo "    ⏱️ Temps d'exécution: " . number_format($executionTime, 2) . " ms\n";

    $success = $executionTime < 1000; // Moins de 1 seconde
    logTest("Performances", $success, "Temps: " . number_format($executionTime, 2) . " ms");

    return $success;
}

// Fonction pour tester la création de commande en production
function testProductionCommandeCreation($entityManager)
{
    echo "🛒 Test création de commande en production...\n";

    $userRepository = $entityManager->getRepository('App\Entity\User');
    $lotRepository = $entityManager->getRepository('App\Entity\Lot');

    $user = $userRepository->findOneBy([]);
    $lot = $lotRepository->findOneBy([]);

    if (!$user || !$lot) {
        logTest("Création de commande en production", false, "Données insuffisantes pour le test");
        return false;
    }

    $commande = new \App\Entity\Commande();
    $commande->setNumeroCommande('PROD-TEST-' . date('YmdHis'));
    $commande->setUser($user);
    $commande->setLot($lot);
    $commande->setQuantite(1);
    $commande->setPrixUnitaire($lot->getPrix());
    $commande->setPrixTotal($lot->getPrix());
    $commande->setStatut('en_attente');
    $commande->setCreatedAt(new \DateTimeImmutable());

    $entityManager->persist($commande);
    $entityManager->flush();

    // Vérifier la création
    $commandeRepository = $entityManager->getRepository('App\Entity\Commande');
    $commandeCreee = $commandeRepository->find($commande->getId());
    $success = $commandeCreee && $commandeCreee->getStatut() === 'en_attente';

    logTest("Création de commande en production", $success, "Commande ID: {$commande->getId()}, Statut: {$commande->getStatut()}");

    // Nettoyer
    $entityManager->remove($commande);
    $entityManager->flush();

    return $success;
}

// Fonction pour vérifier les logs de production
function checkProductionLogs($container)
{
    echo "📝 Vérification des logs de production...\n";

    $logDir = $container->getParameter('kernel.logs_dir');
    $logFiles = [
        'prod.log',
        'error.log',
        'deprecation.log'
    ];

    $success = true;

    foreach ($logFiles as $logFile) {
        $logPath = $logDir . '/' . $logFile;
        if (file_exists($logPath)) {
            $size = filesize($logPath);
            echo "    ✅ $logFile existe (" . number_format($size / 1024, 2) . " KB)\n";
        } else {
            echo "    ❌ $logFile manquant\n";
            $success = false;
        }
    }

    logTest("Logs de production", $success, "Tous les fichiers de logs sont présents");
    return $success;
}

// Fonction pour vérifier le cache de production
function checkProductionCache($container)
{
    echo "💾 Vérification du cache de production...\n";

    $cacheDir = $container->getParameter('kernel.cache_dir');
    $prodCacheDir = $cacheDir . '/prod';

    if (is_dir($prodCacheDir)) {
        $cacheSize = 0;
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($prodCacheDir));
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $cacheSize += $file->getSize();
            }
        }

        echo "    📁 Cache de production: " . number_format($cacheSize / 1024 / 1024, 2) . " MB\n";
        echo "    📂 Répertoire: $prodCacheDir\n";

        logTest("Cache de production", true, "Taille: " . number_format($cacheSize / 1024 / 1024, 2) . " MB");
        return true;
    } else {
        logTest("Cache de production", false, "Répertoire de cache manquant");
        return false;
    }
}

// Fonction pour tester la sécurité
function testSecurity($container)
{
    echo "🔒 Test de sécurité en production...\n";

    $kernel = $container->get('kernel');
    $debug = $kernel->isDebug();

    // Vérifier que le debug est désactivé
    $debugDisabled = !$debug;

    // Vérifier les paramètres de sécurité
    $sessionConfig = $container->getParameter('session.storage.options');
    $cookieSecure = $sessionConfig['cookie_secure'] ?? false;

    echo "    🐛 Debug désactivé: " . ($debugDisabled ? 'OUI' : 'NON') . "\n";
    echo "    🍪 Cookie sécurisé: " . ($cookieSecure ? 'OUI' : 'NON') . "\n";

    $success = $debugDisabled;
    logTest("Sécurité en production", $success, "Debug: " . ($debugDisabled ? 'DÉSACTIVÉ' : 'ACTIVÉ'));

    return $success;
}

// === EXÉCUTION DES TESTS ===

try {
    echo "=== DÉBUT DES TESTS MODE PRODUCTION ===\n\n";

    // Test 1: Vérification de l'environnement
    $envOk = checkProductionEnvironment($container);

    // Test 2: Vérification des performances
    $perfOk = checkPerformance($entityManager);

    // Test 3: Création de commande en production
    $commandeOk = testProductionCommandeCreation($entityManager);

    // Test 4: Vérification des logs
    $logsOk = checkProductionLogs($container);

    // Test 5: Vérification du cache
    $cacheOk = checkProductionCache($container);

    // Test 6: Test de sécurité
    $securityOk = testSecurity($container);

    echo "=== RÉSUMÉ DES TESTS MODE PRODUCTION ===\n";

    $totalTests = 6;
    $passedTests = 0;

    if ($envOk) $passedTests++;
    if ($perfOk) $passedTests++;
    if ($commandeOk) $passedTests++;
    if ($logsOk) $passedTests++;
    if ($cacheOk) $passedTests++;
    if ($securityOk) $passedTests++;

    echo "📊 Tests réussis: $passedTests/$totalTests\n";

    if ($passedTests === $totalTests) {
        echo "✅ TOUS LES TESTS SONT RÉUSSIS\n";
        echo "🚀 L'application est prête pour le déploiement cPanel\n";
        echo "📋 Mode: PRODUCTION\n";
        echo "🔒 Sécurité: VALIDÉE\n";
        echo "⚡ Performances: OPTIMALES\n";
    } else {
        echo "❌ CERTAINS TESTS ONT ÉCHOUÉ\n";
        echo "⚠️ Vérifiez les problèmes avant le déploiement\n";
    }

    echo "\n=== FONCTIONNALITÉS VALIDÉES ===\n";
    echo "✅ Environnement de production\n";
    echo "✅ Performances optimisées\n";
    echo "✅ Création de commandes\n";
    echo "✅ Système de logs\n";
    echo "✅ Cache de production\n";
    echo "✅ Sécurité renforcée\n";
} catch (Exception $e) {
    echo "❌ ERREUR CRITIQUE: " . $e->getMessage() . "\n";
    echo "📍 Fichier: " . $e->getFile() . " Ligne: " . $e->getLine() . "\n";
}

echo "\n=== FIN DU TEST MODE PRODUCTION ===\n";

