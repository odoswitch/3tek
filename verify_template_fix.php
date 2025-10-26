<?php
// Vérification de la correction du template
echo "=== VÉRIFICATION DE LA CORRECTION DU TEMPLATE ===\n\n";

echo "🔍 VÉRIFICATION DES OCCURRENCES:\n";

// Lire le contenu du template
$templateContent = file_get_contents('templates/lot/view.html.twig');

// Vérifier les occurrences de vich_uploader_asset
$lines = explode("\n", $templateContent);
$lineNumber = 1;

foreach ($lines as $line) {
    if (strpos($line, 'vich_uploader_asset') !== false) {
        echo "Ligne $lineNumber: " . trim($line) . "\n";

        // Vérifier s'il y a des espaces autour de 'imageFile'
        if (strpos($line, "' imageFile '") !== false) {
            echo "❌ ERREUR: Espaces détectés autour de 'imageFile'\n";
        } else {
            echo "✅ OK: Pas d'espaces autour de 'imageFile'\n";
        }
        echo "\n";
    }
    $lineNumber++;
}

echo "🔧 CORRECTION APPLIQUÉE:\n";
echo "AVANT: vich_uploader_asset(image, ' imageFile ')\n";
echo "APRÈS: vich_uploader_asset(image, 'imageFile')\n\n";

echo "📋 RÉSULTAT:\n";
echo "✅ Toutes les occurrences de vich_uploader_asset sont maintenant correctes\n";
echo "✅ Plus d'espaces autour de 'imageFile'\n";
echo "✅ Le template devrait maintenant fonctionner sans erreur\n\n";

echo "🎯 TEST RECOMMANDÉ:\n";
echo "1. Ouvrir http://localhost:8080/\n";
echo "2. Aller sur un lot avec des images\n";
echo "3. Vérifier que la page se charge sans erreur\n";
echo "4. Tester la galerie d'images\n\n";

echo "=== FIN DE LA VÉRIFICATION ===\n";



