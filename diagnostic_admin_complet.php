#!/usr/bin/env php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;

// Charger les variables d'environnement
$dotenv = new Dotenv();
$dotenv->load(__DIR__ . '/.env');

// Configuration de la base de données
$databaseUrl = $_ENV['DATABASE_URL'] ?? 'mysql://root:ngamba123@3tek-database-1:3306/db_3tek?serverVersion=8.0&charset=utf8mb4';

echo "=== DIAGNOSTIC COMPLET ADMIN INTERFACE ===\n\n";

// Test de connexion à la base de données
echo "🔍 Test de connexion à la base de données...\n";
try {
    $pdo = new PDO($databaseUrl);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Connexion à la base de données réussie\n";

    // Vérifier les tables
    $tables = ['user', 'commande', 'lot', 'file_attente'];
    foreach ($tables as $table) {
        $stmt = $pdo->query("SELECT COUNT(*) FROM $table");
        $count = $stmt->fetchColumn();
        echo "   - Table $table : $count enregistrements\n";
    }
} catch (Exception $e) {
    echo "❌ Erreur de connexion à la base de données : " . $e->getMessage() . "\n";
}

echo "\n🔍 Vérification des services Symfony...\n";

// Initialiser le kernel Symfony
try {
    $kernel = new App\Kernel('prod', false);
    $kernel->boot();
    $container = $kernel->getContainer();

    echo "✅ Kernel Symfony initialisé\n";

    // Vérifier les services critiques
    $services = [
        'EasyCorp\Bundle\EasyAdminBundle\Registry\CrudControllerRegistry',
        'EasyCorp\Bundle\EasyAdminBundle\Registry\DashboardControllerRegistry',
        'App\Controller\Admin\CommandeCrudController',
        'App\Service\LotLiberationServiceAmeliore',
        'App\Service\StockSynchronizationService'
    ];

    foreach ($services as $service) {
        try {
            $serviceInstance = $container->get($service);
            echo "✅ Service $service : OK\n";
        } catch (Exception $e) {
            echo "❌ Service $service : " . $e->getMessage() . "\n";
        }
    }

    // Vérifier les routes admin
    echo "\n🔍 Vérification des routes admin...\n";
    $router = $container->get('router');
    $routes = $router->getRouteCollection();

    $adminRoutes = [];
    foreach ($routes as $name => $route) {
        if (strpos($name, 'admin_') === 0) {
            $adminRoutes[] = $name;
        }
    }

    echo "✅ " . count($adminRoutes) . " routes admin trouvées\n";

    // Vérifier les permissions du cache
    echo "\n🔍 Vérification des permissions du cache...\n";
    $cacheDir = __DIR__ . '/var/cache/prod';

    if (is_dir($cacheDir)) {
        $perms = fileperms($cacheDir);
        $owner = posix_getpwuid(fileowner($cacheDir));
        $group = posix_getgrgid(filegroup($cacheDir));

        echo "✅ Cache directory existe\n";
        echo "   - Propriétaire : " . ($owner['name'] ?? 'inconnu') . "\n";
        echo "   - Groupe : " . ($group['name'] ?? 'inconnu') . "\n";
        echo "   - Permissions : " . substr(sprintf('%o', $perms), -4) . "\n";

        // Vérifier les sous-répertoires critiques
        $criticalDirs = ['easyadmin', 'asset_mapper', 'pools'];
        foreach ($criticalDirs as $dir) {
            $dirPath = $cacheDir . '/' . $dir;
            if (is_dir($dirPath)) {
                $writable = is_writable($dirPath);
                echo "   - $dir : " . ($writable ? "✅ Écriture OK" : "❌ Pas d'écriture") . "\n";
            } else {
                echo "   - $dir : ❌ N'existe pas\n";
            }
        }
    } else {
        echo "❌ Cache directory n'existe pas\n";
    }

    // Test de création d'une commande simple
    echo "\n🔍 Test de création d'une commande...\n";
    try {
        $entityManager = $container->get('doctrine.orm.entity_manager');
        $userRepo = $entityManager->getRepository(\App\Entity\User::class);
        $lotRepo = $entityManager->getRepository(\App\Entity\Lot::class);

        $user = $userRepo->findOneBy([]);
        $lot = $lotRepo->findOneBy([]);

        if ($user && $lot) {
            echo "✅ Utilisateur et lot trouvés pour le test\n";
            echo "   - Utilisateur : " . $user->getEmail() . "\n";
            echo "   - Lot : " . $lot->getName() . "\n";
        } else {
            echo "❌ Pas d'utilisateur ou de lot trouvé pour le test\n";
        }
    } catch (Exception $e) {
        echo "❌ Erreur lors du test de création : " . $e->getMessage() . "\n";
    }
} catch (Exception $e) {
    echo "❌ Erreur lors de l'initialisation du kernel : " . $e->getMessage() . "\n";
}

echo "\n=== DIAGNOSTIC TERMINÉ ===\n";

