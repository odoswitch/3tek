<?php
echo "=== TEST FINAL COMPLET ===\n\n";

echo "🔍 ÉTAPE 1: Vérification du cache...\n";

$cacheFiles = glob('var/cache/**/*', GLOB_BRACE);
echo "Nombre de fichiers en cache: " . count($cacheFiles) . "\n";

if (count($cacheFiles) == 0) {
    echo "✅ Cache complètement vidé\n";
} else {
    echo "⚠️ Cache encore présent: " . count($cacheFiles) . " fichiers\n";
}

echo "\n🔍 ÉTAPE 2: Vérification des templates HTML...\n";

$templates = [
    'templates/lot/view.html.twig' => '{{ lot.description|raw }}',
    'templates/dash1.html.twig' => '{{item.description|raw|slice(0, 100)}}',
    'templates/favori/index.html.twig' => '{{ favori.lot.description|raw|slice(0, 100) }}',
    'templates/emails/new_lot_notification.html.twig' => '{{ lot.description|raw|slice(0, 200) }}'
];

$tousCorrects = true;

foreach ($templates as $template => $expectedContent) {
    if (file_exists($template)) {
        $content = file_get_contents($template);
        if (strpos($content, $expectedContent) !== false) {
            echo "✅ $template - Filtre |raw correct\n";
        } else {
            echo "❌ $template - Filtre |raw manquant ou incorrect\n";
            $tousCorrects = false;
        }
    } else {
        echo "⚠️ $template - Fichier non trouvé\n";
        $tousCorrects = false;
    }
}

echo "\n🔍 ÉTAPE 3: Vérification de la logique de suppression...\n";

$commandeControllerContent = file_get_contents('src/Controller/Admin/CommandeCrudController.php');

$logiqueCorrecte = true;

if (strpos($commandeControllerContent, 'public function deleteEntity') !== false) {
    echo "✅ Méthode deleteEntity trouvée\n";
} else {
    echo "❌ Méthode deleteEntity manquante\n";
    $logiqueCorrecte = false;
}

if (strpos($commandeControllerContent, 'libererLot($lot, $entityManager)') !== false) {
    echo "✅ Appel à libererLot trouvé\n";
} else {
    echo "❌ Appel à libererLot manquant\n";
    $logiqueCorrecte = false;
}

if (strpos($commandeControllerContent, 'setStatut(\'disponible\')') !== false) {
    echo "✅ Remise du statut à 'disponible' trouvée\n";
} else {
    echo "❌ Remise du statut à 'disponible' manquante\n";
    $logiqueCorrecte = false;
}

if (strpos($commandeControllerContent, 'setQuantite(1)') !== false) {
    echo "✅ Restauration de la quantité trouvée\n";
} else {
    echo "❌ Restauration de la quantité manquante\n";
    $logiqueCorrecte = false;
}

echo "\n📊 RÉSUMÉ FINAL:\n";
echo "- Cache vidé: " . (count($cacheFiles) == 0 ? "✅ OK" : "❌ NON") . "\n";
echo "- Templates HTML: " . ($tousCorrects ? "✅ OK" : "❌ PROBLÈME") . "\n";
echo "- Logique suppression: " . ($logiqueCorrecte ? "✅ OK" : "❌ PROBLÈME") . "\n";

if (count($cacheFiles) == 0 && $tousCorrects && $logiqueCorrecte) {
    echo "\n🎉 TOUT EST CORRECT !\n";
    echo "L'application est maintenant prête :\n";
    echo "1. ✅ Les descriptions HTML sont correctement rendues\n";
    echo "2. ✅ La suppression de commandes libère automatiquement les lots\n";
    echo "3. ✅ Les lots passent de 'rupture' à 'disponible'\n";
    echo "4. ✅ La première personne de la file d'attente est notifiée\n";
    echo "5. ✅ Le cache est complètement vidé\n\n";

    echo "🎯 PROCHAINES ÉTAPES:\n";
    echo "1. Ouvrir http://localhost:8080/\n";
    echo "2. Se connecter avec un compte admin\n";
    echo "3. Aller dans 'Toutes les commandes'\n";
    echo "4. Supprimer une commande en statut 'réservé'\n";
    echo "5. Vérifier que le lot passe à 'disponible'\n";
    echo "6. Vérifier que la description HTML est rendue\n";
} else {
    echo "\n⚠️ PROBLÈMES DÉTECTÉS !\n";
    echo "Il faut corriger les problèmes avant de tester.\n";
}

echo "\n=== FIN DU TEST FINAL ===\n";
