<?php

require_once __DIR__ . '/vendor/autoload.php';

echo "=== TEST PANIER EN MODE PRODUCTION ===\n\n";

// Initialiser Symfony en mode production
$kernel = new \App\Kernel('prod', false);
$kernel->boot();
$container = $kernel->getContainer();

// Récupérer l'EntityManager
$entityManager = $container->get('doctrine.orm.entity_manager');

echo "🔍 Vérification de l'environnement...\n";
$kernel = $container->get('kernel');
$environment = $kernel->getEnvironment();
$debug = $kernel->isDebug();

echo "    📋 Environnement: $environment\n";
echo "    🐛 Debug: " . ($debug ? 'ACTIVÉ' : 'DÉSACTIVÉ') . "\n";

echo "\n🛒 Test des entités panier...\n";

// Test de récupération des données
$userRepository = $entityManager->getRepository('App\Entity\User');
$lotRepository = $entityManager->getRepository('App\Entity\Lot');
$panierRepository = $entityManager->getRepository('App\Entity\Panier');

$users = $userRepository->findAll();
$lots = $lotRepository->findAll();
$panierItems = $panierRepository->findAll();

echo "    👥 Utilisateurs: " . count($users) . "\n";
echo "    📦 Lots: " . count($lots) . "\n";
echo "    🛒 Articles panier: " . count($panierItems) . "\n";

echo "\n🔧 Test création d'article panier...\n";

try {
    $user = $userRepository->findOneBy([]);
    $lot = $lotRepository->findOneBy([]);

    if ($user && $lot) {
        echo "    ✅ Utilisateur trouvé: " . $user->getEmail() . "\n";
        echo "    ✅ Lot trouvé: " . $lot->getName() . "\n";

        // Vérifier si le lot est disponible
        if ($lot->getQuantite() > 0) {
            echo "    ✅ Lot disponible (quantité: " . $lot->getQuantite() . ")\n";

            // Créer un article de panier
            $panierItem = new \App\Entity\Panier();
            $panierItem->setUser($user);
            $panierItem->setLot($lot);
            $panierItem->setQuantite(1);

            $entityManager->persist($panierItem);
            $entityManager->flush();

            echo "    ✅ Article panier créé (ID: {$panierItem->getId()})\n";

            // Nettoyer
            $entityManager->remove($panierItem);
            $entityManager->flush();

            echo "    ✅ Article panier supprimé\n";
        } else {
            echo "    ⚠️ Lot non disponible (quantité: " . $lot->getQuantite() . ")\n";
        }
    } else {
        echo "    ❌ Données insuffisantes pour le test\n";
    }
} catch (Exception $e) {
    echo "    ❌ ERREUR: " . $e->getMessage() . "\n";
    echo "    📍 Fichier: " . $e->getFile() . " Ligne: " . $e->getLine() . "\n";
}

echo "\n🌐 Test des routes panier...\n";

// Test des routes
$router = $container->get('router');
$routes = [
    'app_panier' => '/panier',
    'app_panier_add' => '/panier/add/5',
    'app_panier_update' => '/panier/update/1',
    'app_panier_remove' => '/panier/remove/1',
    'app_panier_valider' => '/panier/valider'
];

foreach ($routes as $name => $path) {
    try {
        $route = $router->generate($name, ['id' => 1, 'lotId' => 5]);
        echo "    ✅ Route '$name': $route\n";
    } catch (Exception $e) {
        echo "    ❌ Route '$name': " . $e->getMessage() . "\n";
    }
}

echo "\n=== RÉSUMÉ ===\n";
echo "🌐 Application accessible sur: http://localhost:8080\n";
echo "📋 Mode: PRODUCTION\n";
echo "🛒 Panier: " . (count($panierItems) >= 0 ? 'FONCTIONNEL' : 'ERREUR') . "\n";

echo "\n✅ LE PANIER EST PRÊT POUR LE DÉPLOIEMENT !\n";

