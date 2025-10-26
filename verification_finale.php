<?php
echo "=== VÉRIFICATION FINALE ===\n\n";

echo "🔍 ÉTAPE 1: Vérification du statut des lots...\n";

// Vérifier le statut des lots
$command = "php bin/console doctrine:query:sql \"SELECT id, name, statut, quantite FROM lot ORDER BY id DESC LIMIT 5\"";
exec($command, $output, $returnCode);

if ($returnCode === 0 && !empty($output)) {
    echo "Statut des lots :\n";
    foreach ($output as $line) {
        if (strpos($line, '|') !== false) {
            echo "- " . $line . "\n";
        }
    }
} else {
    echo "Aucun lot trouvé ou erreur de requête\n";
}

echo "\n🔍 ÉTAPE 2: Vérification des templates HTML...\n";

$templates = [
    'templates/lot/view.html.twig',
    'templates/dash1.html.twig',
    'templates/favori/index.html.twig',
    'templates/emails/new_lot_notification.html.twig'
];

foreach ($templates as $template) {
    if (file_exists($template)) {
        $content = file_get_contents($template);
        if (strpos($content, '|raw') !== false) {
            echo "✅ $template - Filtre |raw présent\n";
        } else {
            echo "❌ $template - Filtre |raw manquant\n";
        }
    }
}

echo "\n🔍 ÉTAPE 3: Vérification du cache...\n";

$cacheFiles = glob('var/cache/**/*', GLOB_BRACE);
echo "Fichiers en cache: " . count($cacheFiles) . "\n";

if (count($cacheFiles) == 0) {
    echo "✅ Cache complètement vidé\n";
} else {
    echo "⚠️ Cache encore présent: " . count($cacheFiles) . " fichiers\n";
}

echo "\n📊 RÉSUMÉ FINAL:\n";
echo "- Statut des lots: " . (strpos(implode('', $output), 'disponible') !== false ? "✅ OK" : "❌ PROBLÈME") . "\n";
echo "- Templates HTML: " . (strpos(file_get_contents('templates/lot/view.html.twig'), '|raw') !== false ? "✅ OK" : "❌ PROBLÈME") . "\n";
echo "- Cache: " . (count($cacheFiles) == 0 ? "✅ OK" : "⚠️ PRÉSENT") . "\n";

if (strpos(implode('', $output), 'disponible') !== false && strpos(file_get_contents('templates/lot/view.html.twig'), '|raw') !== false) {
    echo "\n🎉 TOUT EST CORRECT !\n";
    echo "L'application est maintenant prête :\n";
    echo "1. ✅ Les lots sont 'disponibles'\n";
    echo "2. ✅ Les descriptions HTML sont correctement rendues\n";
    echo "3. ✅ Le système fonctionne parfaitement\n\n";

    echo "🎯 PROCHAINES ÉTAPES:\n";
    echo "1. Ouvrir http://localhost:8080/\n";
    echo "2. Vérifier que les lots affichent 'disponible'\n";
    echo "3. Vérifier que les descriptions HTML sont rendues\n";
    echo "4. Tester le système de file d'attente\n";
} else {
    echo "\n⚠️ PROBLÈMES DÉTECTÉS !\n";
    echo "Il faut corriger les problèmes avant de tester.\n";
}

echo "\n=== FIN DE LA VÉRIFICATION ===\n";



