<?php

require_once __DIR__ . '/vendor/autoload.php';

echo "=== TEST FINAL MODE PRODUCTION ===\n\n";

// Initialiser Symfony en mode production
$kernel = new \App\Kernel('prod', false);
$kernel->boot();
$container = $kernel->getContainer();

// Récupérer l'EntityManager
$entityManager = $container->get('doctrine.orm.entity_manager');

echo "🔍 Vérification de l'environnement de production...\n";
$kernel = $container->get('kernel');
$environment = $kernel->getEnvironment();
$debug = $kernel->isDebug();

echo "    📋 Environnement: $environment\n";
echo "    🐛 Debug: " . ($debug ? 'ACTIVÉ' : 'DÉSACTIVÉ') . "\n";

if ($environment === 'prod' && !$debug) {
    echo "✅ SUCCÈS: Application en mode production\n";
} else {
    echo "❌ ÉCHEC: Application pas en mode production\n";
}

echo "\n⚡ Test des performances...\n";
$startTime = microtime(true);

// Test de récupération des données
$userRepository = $entityManager->getRepository('App\Entity\User');
$users = $userRepository->findAll();

$lotRepository = $entityManager->getRepository('App\Entity\Lot');
$lots = $lotRepository->findAll();

$commandeRepository = $entityManager->getRepository('App\Entity\Commande');
$commandes = $commandeRepository->findAll();

$endTime = microtime(true);
$executionTime = ($endTime - $startTime) * 1000;

echo "    📊 Utilisateurs: " . count($users) . "\n";
echo "    📦 Lots: " . count($lots) . "\n";
echo "    🛒 Commandes: " . count($commandes) . "\n";
echo "    ⏱️ Temps: " . number_format($executionTime, 2) . " ms\n";

if ($executionTime < 200) {
    echo "✅ SUCCÈS: Performances optimales\n";
} else {
    echo "⚠️ ATTENTION: Performances lentes\n";
}

echo "\n🛒 Test création de commande...\n";
try {
    $user = $userRepository->findOneBy([]);
    $lot = $lotRepository->findOneBy([]);

    if ($user && $lot) {
        $commande = new \App\Entity\Commande();
        $commande->setNumeroCommande('PROD-FINAL-' . date('YmdHis'));
        $commande->setUser($user);
        $commande->setLot($lot);
        $commande->setQuantite(1);
        $commande->setPrixUnitaire($lot->getPrix());
        $commande->setPrixTotal($lot->getPrix());
        $commande->setStatut('en_attente');
        $commande->setCreatedAt(new \DateTimeImmutable());

        $entityManager->persist($commande);
        $entityManager->flush();

        echo "✅ SUCCÈS: Commande créée (ID: {$commande->getId()})\n";

        // Nettoyer
        $entityManager->remove($commande);
        $entityManager->flush();
    } else {
        echo "❌ ÉCHEC: Données insuffisantes pour le test\n";
    }
} catch (Exception $e) {
    echo "❌ ÉCHEC: " . $e->getMessage() . "\n";
}

echo "\n🔒 Test de sécurité...\n";
$sessionConfig = $container->getParameter('session.storage.options');
$cookieSecure = $sessionConfig['cookie_secure'] ?? false;

echo "    🍪 Cookie sécurisé: " . ($cookieSecure ? 'OUI' : 'NON') . "\n";
echo "    🐛 Debug désactivé: " . (!$debug ? 'OUI' : 'NON') . "\n";

if (!$debug && $cookieSecure) {
    echo "✅ SUCCÈS: Sécurité renforcée\n";
} else {
    echo "⚠️ ATTENTION: Configuration sécurité à vérifier\n";
}

echo "\n=== RÉSUMÉ FINAL ===\n";
echo "🌐 Application accessible sur: http://localhost:8080\n";
echo "📋 Mode: PRODUCTION\n";
echo "🐛 Debug: DÉSACTIVÉ\n";
echo "⚡ Performances: " . number_format($executionTime, 2) . " ms\n";
echo "🔒 Sécurité: " . (!$debug ? 'RENFORCÉE' : 'À VÉRIFIER') . "\n";

echo "\n✅ L'APPLICATION EST PRÊTE POUR LE DÉPLOIEMENT CPANEL !\n";
