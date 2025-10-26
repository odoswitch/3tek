<?php
echo "=== DIAGNOSTIC COMPLET DE L'APPLICATION ===\n\n";

echo "🔍 ÉTAPE 1: Vérification de tous les templates...\n";
$templates = glob('templates/**/*.twig', GLOB_BRACE);
$erreurs = [];

foreach ($templates as $template) {
    $content = file_get_contents($template);
    if (strpos($content, "' imageFile '") !== false) {
        $erreurs[] = $template;
        echo "❌ ERREUR dans $template: Espaces détectés !\n";
    }
}

if (empty($erreurs)) {
    echo "✅ Aucun espace détecté dans les templates\n";
}

echo "\n🔍 ÉTAPE 2: Vérification de l'entité LotImage...\n";
$lotImageContent = file_get_contents('src/Entity/LotImage.php');
if (strpos($lotImageContent, "fileNameProperty: 'imageName'") !== false) {
    echo "✅ Mapping VichUploader correct dans l'entité\n";
} else {
    echo "❌ Problème de mapping dans l'entité\n";
}

echo "\n🔍 ÉTAPE 3: Vérification de la configuration VichUploader...\n";
$vichConfig = file_get_contents('config/packages/vich_uploader.yaml');
if (strpos($vichConfig, "lot_images:") !== false) {
    echo "✅ Mapping 'lot_images' trouvé dans la configuration\n";
} else {
    echo "❌ Mapping 'lot_images' manquant dans la configuration\n";
}

echo "\n🔍 ÉTAPE 4: Vérification du cache...\n";
$cacheFiles = glob('var/cache/**/*', GLOB_BRACE);
echo "Nombre de fichiers en cache: " . count($cacheFiles) . "\n";

echo "\n🔍 ÉTAPE 5: Vérification des logs d'erreur...\n";
if (file_exists('var/log/dev.log')) {
    $logContent = file_get_contents('var/log/dev.log');
    $lines = explode("\n", $logContent);
    $lastLines = array_slice($lines, -20);
    
    foreach ($lastLines as $line) {
        if (strpos($line, 'MappingNotFoundException') !== false || strpos($line, 'imageFile') !== false) {
            echo "⚠️ Erreur trouvée: " . substr($line, 0, 200) . "...\n";
        }
    }
}

echo "\n📊 RÉSUMÉ DU DIAGNOSTIC:\n";
echo "- Templates avec espaces: " . count($erreurs) . "\n";
echo "- Configuration VichUploader: " . (strpos($vichConfig, "lot_images:") !== false ? "OK" : "ERREUR") . "\n";
echo "- Fichiers en cache: " . count($cacheFiles) . "\n";

echo "\n=== FIN DU DIAGNOSTIC ===\n";
?>



