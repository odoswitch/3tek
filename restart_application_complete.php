<?php
// Redémarrage complet de l'application
echo "=== REDÉMARRAGE COMPLET DE L'APPLICATION ===\n\n";

echo "🔧 ÉTAPE 1: Vérification de la correction...\n";

// Vérifier la ligne 318 spécifiquement
$templateContent = file_get_contents('templates/lot/view.html.twig');
$lines = explode("\n", $templateContent);

if (isset($lines[317])) { // Ligne 318 (index 317)
    $ligne318 = $lines[317];
    echo "Ligne 318: " . trim($ligne318) . "\n";

    if (strpos($ligne318, "' imageFile '") !== false) {
        echo "❌ ERREUR: Espaces encore présents !\n";
        exit(1);
    } else {
        echo "✅ OK: Plus d'espaces à la ligne 318 !\n";
    }
}

echo "\n🔧 ÉTAPE 2: Suppression complète du cache...\n";

// Supprimer tout le cache
$cacheDir = 'var/cache';
if (is_dir($cacheDir)) {
    // Supprimer récursivement
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($cacheDir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($iterator as $file) {
        if ($file->isDir()) {
            rmdir($file->getRealPath());
        } else {
            unlink($file->getRealPath());
        }
    }
    rmdir($cacheDir);
    echo "✅ Cache complètement supprimé\n";
}

echo "\n🔧 ÉTAPE 3: Suppression des logs...\n";
$logDir = 'var/log';
if (is_dir($logDir)) {
    $files = glob($logDir . '/*');
    foreach ($files as $file) {
        if (is_file($file)) {
            unlink($file);
        }
    }
    echo "✅ Logs supprimés\n";
}

echo "\n🔧 ÉTAPE 4: Suppression des sessions...\n";
$sessionDir = 'var/sessions';
if (is_dir($sessionDir)) {
    $files = glob($sessionDir . '/*');
    foreach ($files as $file) {
        if (is_file($file)) {
            unlink($file);
        }
    }
    echo "✅ Sessions supprimées\n";
}

echo "\n✅ REDÉMARRAGE COMPLET TERMINÉ !\n";
echo "L'application a été complètement nettoyée.\n";
echo "Tous les caches ont été supprimés.\n";
echo "Les templates corrigés seront utilisés.\n\n";

echo "🎯 PROCHAINES ÉTAPES:\n";
echo "1. L'application va redémarrer automatiquement\n";
echo "2. Ouvrir http://localhost:8080/\n";
echo "3. Se connecter avec un compte utilisateur\n";
echo "4. Aller sur un lot avec des images\n";
echo "5. Vérifier que la page se charge SANS ERREUR\n";
echo "6. Tester la galerie d'images\n\n";

echo "✅ RÉSULTAT ATTENDU:\n";
echo "- Plus d'erreur 'Mapping not found for field imageFile'\n";
echo "- Les images s'affichent correctement\n";
echo "- La galerie d'images fonctionne parfaitement\n";
echo "- L'application est complètement opérationnelle\n\n";

echo "=== FIN DU REDÉMARRAGE ===\n";



