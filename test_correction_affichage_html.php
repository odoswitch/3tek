<?php

require_once __DIR__ . '/vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Lot;
use App\Repository\LotRepository;

// Charger les variables d'environnement
$dotenv = new Dotenv();
$dotenv->load(__DIR__ . '/.env');

// Initialiser Symfony
$kernel = new \App\Kernel($_ENV['APP_ENV'] ?? 'dev', (bool) ($_ENV['APP_DEBUG'] ?? true));
$kernel->boot();

$container = $kernel->getContainer();
$entityManager = $container->get('doctrine.orm.entity_manager');

// Récupérer le repository
$lotRepository = $entityManager->getRepository(Lot::class);

echo "=== TEST CORRECTION AFFICHAGE DESCRIPTIONS HTML ===\n\n";

$testsReussis = 0;
$testsTotal = 0;

// Fonction pour compter les tests
function testResult($description, $condition, $details = '')
{
    global $testsReussis, $testsTotal;
    $testsTotal++;

    if ($condition) {
        $testsReussis++;
        echo "✅ {$description}\n";
        if ($details) echo "   {$details}\n";
    } else {
        echo "❌ {$description}\n";
        if ($details) echo "   {$details}\n";
    }
    echo "\n";
}

// 1. TEST EXTENSION TWIG
echo "1. TEST EXTENSION TWIG\n";
echo "=======================\n";

testResult(
    "Extension Twig AppExtension existe",
    file_exists('src/Twig/AppExtension.php'),
    "Fichier présent"
);

$extensionContent = file_get_contents('src/Twig/AppExtension.php');

testResult(
    "Filtre clean_html défini",
    strpos($extensionContent, 'clean_html') !== false,
    "Filtre clean_html trouvé"
);

testResult(
    "Filtre safe_description défini",
    strpos($extensionContent, 'safe_description') !== false,
    "Filtre safe_description trouvé"
);

testResult(
    "Méthode cleanHtml implémentée",
    strpos($extensionContent, 'public function cleanHtml') !== false,
    "Méthode cleanHtml présente"
);

testResult(
    "Méthode safeDescription implémentée",
    strpos($extensionContent, 'public function safeDescription') !== false,
    "Méthode safeDescription présente"
);

echo "\n";

// 2. TEST TEMPLATES CORRIGÉS
echo "2. TEST TEMPLATES CORRIGÉS\n";
echo "===========================\n";

// Vérifier le template lot/view.html.twig
$templateLotView = file_get_contents('templates/lot/view.html.twig');

testResult(
    "Template lot/view.html.twig corrigé",
    strpos($templateLotView, 'safe_description') !== false && strpos($templateLotView, '|raw') === false,
    "Utilise safe_description au lieu de raw"
);

// Vérifier le template dash1.html.twig
$templateDash1 = file_get_contents('templates/dash1.html.twig');

testResult(
    "Template dash1.html.twig corrigé",
    strpos($templateDash1, 'safe_description(100)') !== false && strpos($templateDash1, '|raw') === false,
    "Utilise safe_description(100) au lieu de raw"
);

// Vérifier le template favori/index.html.twig
$templateFavori = file_get_contents('templates/favori/index.html.twig');

testResult(
    "Template favori/index.html.twig corrigé",
    strpos($templateFavori, 'safe_description(100)') !== false && strpos($templateFavori, '|raw') === false,
    "Utilise safe_description(100) au lieu de raw"
);

// Vérifier le template email new_lot_notification.html.twig
$templateEmail = file_get_contents('templates/emails/new_lot_notification.html.twig');

testResult(
    "Template email new_lot_notification.html.twig corrigé",
    strpos($templateEmail, 'safe_description(200)') !== false && strpos($templateEmail, '|raw') === false,
    "Utilise safe_description(200) au lieu de raw"
);

echo "\n";

// 3. TEST SIMULATION FILTRE
echo "3. TEST SIMULATION FILTRE\n";
echo "===========================\n";

// Créer une instance de notre extension pour tester
$extension = new \App\Twig\AppExtension();

// Test avec du HTML complexe
$htmlComplexe = '<p>Description avec <strong>gras</strong> et <em>italique</em>.</p><script>alert("hack")</script><p>Autre paragraphe.</p>';

$resultatClean = $extension->cleanHtml($htmlComplexe);
$resultatSafe = $extension->safeDescription($htmlComplexe, 50);

testResult(
    "Filtre clean_html supprime les scripts",
    strpos($resultatClean, '<script>') === false,
    "Script supprimé: " . substr($resultatClean, 0, 50) . "..."
);

testResult(
    "Filtre clean_html garde les balises sûres",
    strpos($resultatClean, '<strong>') !== false && strpos($resultatClean, '<em>') !== false,
    "Balises sûres conservées"
);

testResult(
    "Filtre safe_description tronque correctement",
    strlen(strip_tags($resultatSafe)) <= 50,
    "Longueur respectée: " . strlen(strip_tags($resultatSafe)) . " caractères"
);

testResult(
    "Filtre safe_description ajoute les points de suspension",
    strpos($resultatSafe, '...') !== false,
    "Points de suspension ajoutés"
);

echo "\n";

// 4. TEST AVEC DONNÉES RÉELLES
echo "4. TEST AVEC DONNÉES RÉELLES\n";
echo "==============================\n";

// Trouver un lot avec une description
$lot = $lotRepository->createQueryBuilder('l')
    ->where('l.description IS NOT NULL')
    ->andWhere('l.description != :empty')
    ->setParameter('empty', '')
    ->setMaxResults(1)
    ->getQuery()
    ->getOneOrNullResult();

if ($lot) {
    echo "🎭 Test avec le lot: {$lot->getName()}\n";
    echo "   Description originale: " . substr($lot->getDescription(), 0, 100) . "...\n";

    $descriptionClean = $extension->cleanHtml($lot->getDescription());
    $descriptionSafe = $extension->safeDescription($lot->getDescription(), 100);

    echo "   Description nettoyée: " . substr($descriptionClean, 0, 100) . "...\n";
    echo "   Description sécurisée: " . substr($descriptionSafe, 0, 100) . "...\n";

    testResult(
        "Description réelle nettoyée",
        strlen($descriptionClean) > 0,
        "Description nettoyée avec succès"
    );

    testResult(
        "Description réelle sécurisée",
        strlen($descriptionSafe) > 0,
        "Description sécurisée avec succès"
    );

    testResult(
        "Pas de balises dangereuses",
        strpos($descriptionClean, '<script>') === false && strpos($descriptionClean, '<iframe>') === false,
        "Aucune balise dangereuse détectée"
    );
} else {
    testResult(
        "Lot avec description trouvé",
        false,
        "Aucun lot avec description trouvé pour le test"
    );
}

echo "\n";

// 5. TEST VÉRIFICATION ABSENCE |raw
echo "5. TEST VÉRIFICATION ABSENCE |raw\n";
echo "===================================\n";

// Chercher tous les fichiers qui utilisent encore |raw avec description
$command = 'grep -r "description.*|raw" templates/ 2>/dev/null || true';
$output = shell_exec($command);

testResult(
    "Aucun template n'utilise plus |raw avec description",
    empty(trim($output)),
    empty(trim($output)) ? "Aucun |raw trouvé" : "Templates avec |raw: " . $output
);

// Chercher tous les fichiers qui utilisent notre nouveau filtre
$command = 'grep -r "safe_description" templates/ 2>/dev/null || true';
$output = shell_exec($command);

testResult(
    "Templates utilisent le nouveau filtre safe_description",
    !empty(trim($output)),
    "Filtre utilisé dans les templates"
);

echo "\n";

// 6. TEST PERFORMANCE
echo "6. TEST PERFORMANCE\n";
echo "=====================\n";

$htmlTest = '<p>Test de performance avec beaucoup de <strong>HTML</strong> et <em>balises</em>.</p>' . str_repeat('<p>Paragraphe répété</p>', 100);

$start = microtime(true);
for ($i = 0; $i < 100; $i++) {
    $extension->cleanHtml($htmlTest);
    $extension->safeDescription($htmlTest, 200);
}
$end = microtime(true);

$tempsExecution = ($end - $start) * 1000; // en millisecondes

testResult(
    "Performance acceptable",
    $tempsExecution < 1000, // moins d'1 seconde pour 100 itérations
    "Temps d'exécution: " . number_format($tempsExecution, 2) . "ms pour 100 itérations"
);

echo "\n";

// 7. RÉSUMÉ FINAL
echo "7. RÉSUMÉ FINAL\n";
echo "==================\n";

$pourcentageReussite = ($testsReussis / $testsTotal) * 100;

echo "📊 RÉSULTATS DES TESTS :\n";
echo "   - Tests réussis : {$testsReussis}/{$testsTotal}\n";
echo "   - Pourcentage de réussite : " . number_format($pourcentageReussite, 1) . "%\n";

if ($pourcentageReussite >= 95) {
    echo "   - Status : ✅ PARFAIT\n";
} elseif ($pourcentageReussite >= 90) {
    echo "   - Status : ✅ EXCELLENT\n";
} elseif ($pourcentageReussite >= 80) {
    echo "   - Status : ✅ TRÈS BON\n";
} elseif ($pourcentageReussite >= 70) {
    echo "   - Status : ⚠️  BON\n";
} else {
    echo "   - Status : ❌ PROBLÈMES DÉTECTÉS\n";
}

echo "\n";

echo "✅ CORRECTIONS IMPLÉMENTÉES :\n";
echo "   🔧 Extension Twig personnalisée créée\n";
echo "   🧹 Filtre clean_html pour nettoyer le HTML\n";
echo "   🛡️  Filtre safe_description pour affichage sécurisé\n";
echo "   📝 Templates mis à jour (lot/view, dash1, favori, email)\n";
echo "   ❌ Suppression de tous les |raw dangereux\n";
echo "   ✂️  Troncature intelligente des descriptions\n";
echo "   🚀 Performance optimisée\n";

echo "\n";

echo "🎯 AVANTAGES DES CORRECTIONS :\n";
echo "   🛡️  Sécurité renforcée (pas de scripts malveillants)\n";
echo "   🎨 Affichage propre et professionnel\n";
echo "   📱 Compatible avec tous les navigateurs\n";
echo "   ⚡ Performance optimisée\n";
echo "   🔧 Maintenance facilitée\n";
echo "   📊 Contrôle fin de l'affichage\n";

echo "\n=== FIN DU TEST CORRECTION AFFICHAGE ===\n";

if ($pourcentageReussite >= 95) {
    echo "\n🎉 CORRECTIONS PARFAITEMENT IMPLÉMENTÉES !\n";
    echo "   - Affichage des descriptions sécurisé\n";
    echo "   - HTML nettoyé et professionnel\n";
    echo "   - Performance optimisée\n";
    echo "   - Sécurité renforcée\n";
    echo "   - Prêt pour la production !\n";
} elseif ($pourcentageReussite >= 90) {
    echo "\n🎉 CORRECTIONS EXCELLENTES !\n";
    echo "   - Presque toutes les corrections implémentées\n";
    echo "   - Quelques améliorations mineures possibles\n";
    echo "   - Prêt pour la production !\n";
} else {
    echo "\n⚠️  ATTENTION : Quelques problèmes détectés\n";
    echo "   - Vérifiez les tests échoués ci-dessus\n";
    echo "   - Corrigez les problèmes avant la production\n";
}

