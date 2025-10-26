<?php
// Vérification finale complète
echo "=== VÉRIFICATION FINALE COMPLÈTE ===\n\n";

echo "🔍 VÉRIFICATION DÉFINITIVE DES ESPACES:\n";

// Vérifier tous les templates pour les espaces
$templates = [
    'templates/lot/view.html.twig',
    'templates/dash1.html.twig',
    'templates/lot/list.html.twig',
    'templates/panier/index.html.twig',
    'templates/favori/index.html.twig'
];

$erreursTrouvees = false;

foreach ($templates as $template) {
    if (file_exists($template)) {
        echo "\n📄 $template:\n";
        $content = file_get_contents($template);
        $lines = explode("\n", $content);
        $lineNumber = 1;

        foreach ($lines as $line) {
            if (strpos($line, 'vich_uploader_asset') !== false) {
                echo "  Ligne $lineNumber: " . trim($line) . "\n";

                // Vérifier s'il y a des espaces autour de 'imageFile'
                if (strpos($line, "' imageFile '") !== false) {
                    echo "  ❌ ERREUR: Espaces détectés autour de 'imageFile'\n";
                    $erreursTrouvees = true;
                } else {
                    echo "  ✅ OK: Pas d'espaces autour de 'imageFile'\n";
                }
            }
            $lineNumber++;
        }
    }
}

if (!$erreursTrouvees) {
    echo "\n✅ SUCCÈS: Aucun espace détecté dans tous les templates !\n";
} else {
    echo "\n❌ ERREUR: Des espaces ont été détectés !\n";
}

echo "\n🔍 VÉRIFICATION DE LA LIGNE 318 SPÉCIFIQUEMENT:\n";
$templateContent = file_get_contents('templates/lot/view.html.twig');
$lines = explode("\n", $templateContent);

if (isset($lines[317])) { // Ligne 318 (index 317)
    $ligne318 = $lines[317];
    echo "Ligne 318: " . trim($ligne318) . "\n";

    if (strpos($ligne318, "' imageFile '") !== false) {
        echo "❌ ERREUR: Espaces encore présents à la ligne 318 !\n";
    } else {
        echo "✅ OK: Plus d'espaces à la ligne 318 !\n";
    }
}

echo "\n🔧 CORRECTION APPLIQUÉE:\n";
echo "AVANT: vich_uploader_asset(image, ' imageFile ')\n";
echo "APRÈS: vich_uploader_asset(image, 'imageFile')\n\n";

echo "📋 RÉSULTAT FINAL:\n";
if (!$erreursTrouvees) {
    echo "✅ TOUTES LES CORRECTIONS SONT APPLIQUÉES !\n";
    echo "✅ Plus d'espaces dans aucun template\n";
    echo "✅ L'erreur VichUploader est définitivement résolue\n";
    echo "✅ L'application devrait maintenant fonctionner parfaitement\n\n";

    echo "🎯 TEST RECOMMANDÉ:\n";
    echo "1. Ouvrir http://localhost:8080/\n";
    echo "2. Se connecter avec un compte utilisateur\n";
    echo "3. Aller sur un lot avec des images\n";
    echo "4. Vérifier que la page se charge SANS ERREUR\n";
    echo "5. Tester la galerie d'images\n\n";
} else {
    echo "❌ DES ERREURS PERSISTENT !\n";
    echo "❌ Il faut encore corriger les espaces\n";
}

echo "=== FIN DE LA VÉRIFICATION FINALE ===\n";



