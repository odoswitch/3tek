<?php
echo "=== DIAGNOSTIC COMPLET FINAL ===\n\n";

echo "🔍 ÉTAPE 1: Vérification de la logique de suppression...\n";

$commandeControllerContent = file_get_contents('src/Controller/Admin/CommandeCrudController.php');

if (strpos($commandeControllerContent, 'public function deleteEntity') !== false) {
    echo "✅ Méthode deleteEntity trouvée\n";
} else {
    echo "❌ Méthode deleteEntity manquante\n";
}

if (strpos($commandeControllerContent, 'libererLot($lot, $entityManager)') !== false) {
    echo "✅ Appel à libererLot trouvé\n";
} else {
    echo "❌ Appel à libererLot manquant\n";
}

echo "\n🔍 ÉTAPE 2: Vérification de la logique de libération...\n";

if (strpos($commandeControllerContent, 'setStatut(\'disponible\')') !== false) {
    echo "✅ Remise du statut à 'disponible' trouvée\n";
} else {
    echo "❌ Remise du statut à 'disponible' manquante\n";
}

if (strpos($commandeControllerContent, 'setQuantite(1)') !== false) {
    echo "✅ Restauration de la quantité trouvée\n";
} else {
    echo "❌ Restauration de la quantité manquante\n";
}

echo "\n🔍 ÉTAPE 3: Vérification des templates HTML...\n";

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
            echo "✅ $template utilise le filtre |raw\n";
        } else {
            echo "❌ $template n'utilise pas le filtre |raw\n";
        }
    }
}

echo "\n🔍 ÉTAPE 4: Vérification du cache...\n";

$cacheFiles = glob('var/cache/**/*', GLOB_BRACE);
echo "Nombre de fichiers en cache: " . count($cacheFiles) . "\n";

if (count($cacheFiles) > 0) {
    echo "⚠️ Cache présent - peut causer des problèmes de rendu\n";
} else {
    echo "✅ Cache vide\n";
}

echo "\n🔍 ÉTAPE 5: Vérification des logs d'erreur...\n";

if (file_exists('var/log/dev.log')) {
    $logContent = file_get_contents('var/log/dev.log');
    $lines = explode("\n", $logContent);
    $lastLines = array_slice($lines, -10);

    foreach ($lastLines as $line) {
        if (strpos($line, 'ERROR') !== false || strpos($line, 'Exception') !== false) {
            echo "⚠️ Erreur trouvée: " . substr($line, 0, 100) . "...\n";
        }
    }
}

echo "\n📊 RÉSUMÉ:\n";
echo "- Logique suppression: " . (strpos($commandeControllerContent, 'public function deleteEntity') !== false ? "OK" : "MANQUANT") . "\n";
echo "- Libération lot: " . (strpos($commandeControllerContent, 'setStatut(\'disponible\')') !== false ? "OK" : "MANQUANT") . "\n";
echo "- Filtre HTML: " . (strpos(file_get_contents('templates/lot/view.html.twig'), '|raw') !== false ? "OK" : "MANQUANT") . "\n";
echo "- Cache: " . (count($cacheFiles) > 0 ? "PRÉSENT" : "VIDE") . "\n";

echo "\n🎯 ACTIONS RECOMMANDÉES:\n";
echo "1. Vider complètement le cache\n";
echo "2. Redémarrer les conteneurs\n";
echo "3. Tester la suppression d'une commande\n";
echo "4. Vérifier que le lot passe à 'disponible'\n";
echo "5. Vérifier que la description HTML est rendue\n\n";

echo "=== FIN DU DIAGNOSTIC ===\n";



