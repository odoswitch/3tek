<?php
// Script pour vider le cache
echo "=== VIDAGE DU CACHE ===\n\n";

echo "🔧 Suppression du cache Symfony...\n";

// Supprimer le cache
$cacheDir = 'var/cache';
if (is_dir($cacheDir)) {
    $files = glob($cacheDir . '/*');
    foreach ($files as $file) {
        if (is_file($file)) {
            unlink($file);
            echo "✅ Fichier supprimé: " . basename($file) . "\n";
        }
    }

    // Supprimer les sous-dossiers
    $subdirs = glob($cacheDir . '/*', GLOB_ONLYDIR);
    foreach ($subdirs as $subdir) {
        $files = glob($subdir . '/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        rmdir($subdir);
        echo "✅ Dossier supprimé: " . basename($subdir) . "\n";
    }
} else {
    echo "⚠️ Dossier cache non trouvé\n";
}

echo "\n🔧 Suppression du cache EasyAdmin...\n";
$easyAdminCache = 'var/cache/easyadmin';
if (is_dir($easyAdminCache)) {
    $files = glob($easyAdminCache . '/*');
    foreach ($files as $file) {
        if (is_file($file)) {
            unlink($file);
            echo "✅ Cache EasyAdmin supprimé: " . basename($file) . "\n";
        }
    }
}

echo "\n✅ CACHE VIDÉ AVEC SUCCÈS !\n";
echo "L'application devrait maintenant utiliser les templates corrigés.\n\n";

echo "🎯 PROCHAINES ÉTAPES:\n";
echo "1. Ouvrir http://localhost:8080/\n";
echo "2. Se connecter avec un compte utilisateur\n";
echo "3. Aller sur un lot avec des images\n";
echo "4. Vérifier que la page se charge SANS ERREUR\n";
echo "5. Tester la galerie d'images\n\n";

echo "=== FIN DU VIDAGE ===\n";
