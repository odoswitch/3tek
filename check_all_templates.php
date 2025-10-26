<?php
// Vérification de tous les templates
echo "=== VÉRIFICATION DE TOUS LES TEMPLATES ===\n\n";

echo "🔍 VÉRIFICATION DES OCCURRENCES VICHUPLOADER:\n";

// Templates à vérifier
$templates = [
    'templates/lot/view.html.twig',
    'templates/dash1.html.twig',
    'templates/lot/list.html.twig',
    'templates/panier/index.html.twig',
    'templates/favori/index.html.twig'
];

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
                } else {
                    echo "  ✅ OK: Pas d'espaces autour de 'imageFile'\n";
                }
            }
            $lineNumber++;
        }
    } else {
        echo "\n📄 $template: ❌ FICHIER NON TROUVÉ\n";
    }
}

echo "\n🔍 VÉRIFICATION DES AUTRES ERREURS POTENTIELLES:\n";

// Vérifier les erreurs de syntaxe Twig communes
$commonErrors = [
    '{{ ' => 'Espaces dans les balises Twig',
    '}} ' => 'Espaces dans les balises Twig',
    '{% ' => 'Espaces dans les balises Twig',
    '%} ' => 'Espaces dans les balises Twig'
];

foreach ($templates as $template) {
    if (file_exists($template)) {
        echo "\n📄 $template:\n";
        $content = file_get_contents($template);

        foreach ($commonErrors as $pattern => $description) {
            if (strpos($content, $pattern) !== false) {
                echo "  ⚠️ ATTENTION: $description détectée\n";
            }
        }
    }
}

echo "\n✅ VÉRIFICATION TERMINÉE !\n";
echo "Tous les templates ont été vérifiés pour les erreurs VichUploader.\n";

echo "\n=== FIN DE LA VÉRIFICATION ===\n";



