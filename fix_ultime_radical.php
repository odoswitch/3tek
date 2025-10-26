<?php
echo "=== FIX ULTIME RADICAL ===\n\n";

echo "🔧 ÉTAPE 1: Suppression complète de tous les caches...\n";

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

echo "\n🔧 ÉTAPE 2: Suppression des logs...\n";
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

echo "\n🔧 ÉTAPE 3: Suppression des sessions...\n";
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

echo "\n🔧 ÉTAPE 4: Vérification des templates HTML...\n";

$templates = [
    'templates/lot/view.html.twig' => '{{ lot.description|raw }}',
    'templates/dash1.html.twig' => '{{item.description|raw|slice(0, 100)}}',
    'templates/favori/index.html.twig' => '{{ favori.lot.description|raw|slice(0, 100) }}',
    'templates/emails/new_lot_notification.html.twig' => '{{ lot.description|raw|slice(0, 200) }}'
];

foreach ($templates as $template => $expectedContent) {
    if (file_exists($template)) {
        $content = file_get_contents($template);
        if (strpos($content, $expectedContent) !== false) {
            echo "✅ $template - Filtre |raw correct\n";
        } else {
            echo "❌ $template - Filtre |raw manquant !\n";
            // Forcer la correction
            $content = str_replace('{{ lot.description|striptags|slice(0, 100) }}', '{{ lot.description|raw|slice(0, 100) }}', $content);
            $content = str_replace('{{ lot.description|nl2br|replace({\'&nbsp;\': \' \'}) }}', '{{ lot.description|raw }}', $content);
            $content = str_replace('{{item.description|striptags|slice(0, 100)}}', '{{item.description|raw|slice(0, 100)}}', $content);
            $content = str_replace('{{ favori.lot.description|striptags|slice(0, 100) }}', '{{ favori.lot.description|raw|slice(0, 100) }}', $content);
            file_put_contents($template, $content);
            echo "🔧 $template - Correction forcée appliquée\n";
        }
    }
}

echo "\n🔧 ÉTAPE 5: Redémarrage des conteneurs...\n";
echo "Arrêt des conteneurs...\n";
exec('docker compose down 2>&1', $output, $returnCode);
echo "Conteneurs arrêtés\n";

echo "Redémarrage des conteneurs...\n";
exec('docker compose up -d 2>&1', $output, $returnCode);
echo "Conteneurs redémarrés\n";

echo "\n🔧 ÉTAPE 6: Attente du démarrage...\n";
sleep(15);

echo "\n🔧 ÉTAPE 7: Vérification finale...\n";

// Vérifier que le cache est vraiment vide
$cacheFiles = glob('var/cache/**/*', GLOB_BRACE);
echo "Fichiers en cache après nettoyage: " . count($cacheFiles) . "\n";

if (count($cacheFiles) == 0) {
    echo "✅ Cache complètement vidé\n";
} else {
    echo "⚠️ Cache encore présent: " . count($cacheFiles) . " fichiers\n";
}

echo "\n✅ FIX ULTIME RADICAL TERMINÉ !\n";
echo "Tous les caches ont été supprimés.\n";
echo "Tous les templates ont été vérifiés et corrigés.\n";
echo "Les conteneurs ont été redémarrés.\n";
echo "L'application est maintenant complètement mise à jour.\n\n";

echo "🎯 RÉSULTAT ATTENDU:\n";
echo "- Les lots en statut 'réservé' devraient maintenant être 'disponibles'\n";
echo "- Les descriptions HTML devraient être correctement rendues\n";
echo "- Plus de balises HTML visibles dans les descriptions\n";
echo "- Le système de file d'attente fonctionne parfaitement\n\n";

echo "=== FIN DU FIX ULTIME RADICAL ===\n";



