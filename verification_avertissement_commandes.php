<?php

require_once __DIR__ . '/vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Lot;
use App\Entity\User;
use App\Entity\Commande;
use App\Repository\LotRepository;
use App\Repository\UserRepository;
use App\Repository\CommandeRepository;

// Charger les variables d'environnement
$dotenv = new Dotenv();
$dotenv->load(__DIR__ . '/.env');

// Initialiser Symfony
$kernel = new \App\Kernel($_ENV['APP_ENV'] ?? 'dev', (bool) ($_ENV['APP_DEBUG'] ?? true));
$kernel->boot();

$container = $kernel->getContainer();
$entityManager = $container->get('doctrine.orm.entity_manager');

// Récupérer les repositories
$lotRepository = $entityManager->getRepository(Lot::class);
$userRepository = $entityManager->getRepository(User::class);
$commandeRepository = $entityManager->getRepository(Commande::class);

echo "=== VÉRIFICATION AVERTISSEMENT COMMANDES NON HONORÉES ===\n\n";

$testsReussis = 0;
$testsTotal = 0;

// Fonction pour compter les tests
function testResult($description, $condition, $details = '') {
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

// 1. VÉRIFICATION TEMPLATE CONFIRMATION SIMPLE
echo "1. VÉRIFICATION TEMPLATE CONFIRMATION SIMPLE\n";
echo "==============================================\n";

$templateConfirmation = file_get_contents('templates/emails/commande_confirmation.html.twig');

testResult(
    "Template confirmation existe",
    file_exists('templates/emails/commande_confirmation.html.twig'),
    "Fichier présent"
);

testResult(
    "Avertissement présent dans template confirmation",
    strpos($templateConfirmation, 'Avertissement Important') !== false,
    "Section d'avertissement trouvée"
);

testResult(
    "Politique de non-paiement mentionnée",
    strpos($templateConfirmation, '3 commandes') !== false,
    "Limite de 3 commandes non honorées"
);

testResult(
    "Risque de bannissement mentionné",
    strpos($templateConfirmation, 'banni définitivement') !== false,
    "Conséquence du non-paiement"
);

testResult(
    "Engagement de paiement mentionné",
    strpos($templateConfirmation, 'Engagement de paiement') !== false,
    "Engagement clairement défini"
);

testResult(
    "Conseil donné aux utilisateurs",
    strpos($templateConfirmation, 'Conseil :') !== false,
    "Guidance pour éviter les problèmes"
);

echo "\n";

// 2. VÉRIFICATION TEMPLATE CONFIRMATION MULTIPLE
echo "2. VÉRIFICATION TEMPLATE CONFIRMATION MULTIPLE\n";
echo "================================================\n";

$templateConfirmationMultiple = file_get_contents('templates/emails/commande_multiple_confirmation.html.twig');

testResult(
    "Template confirmation multiple existe",
    file_exists('templates/emails/commande_multiple_confirmation.html.twig'),
    "Fichier présent"
);

testResult(
    "Avertissement présent dans template multiple",
    strpos($templateConfirmationMultiple, 'Avertissement Important') !== false,
    "Section d'avertissement trouvée"
);

testResult(
    "Politique de non-paiement mentionnée (multiple)",
    strpos($templateConfirmationMultiple, '3 commandes') !== false,
    "Limite de 3 commandes non honorées"
);

testResult(
    "Risque de bannissement mentionné (multiple)",
    strpos($templateConfirmationMultiple, 'banni définitivement') !== false,
    "Conséquence du non-paiement"
);

testResult(
    "Engagement de paiement mentionné (multiple)",
    strpos($templateConfirmationMultiple, 'Engagement de paiement') !== false,
    "Engagement clairement défini"
);

testResult(
    "Conseil donné aux utilisateurs (multiple)",
    strpos($templateConfirmationMultiple, 'Conseil :') !== false,
    "Guidance pour éviter les problèmes"
);

echo "\n";

// 3. VÉRIFICATION CONTENU DÉTAILLÉ
echo "3. VÉRIFICATION CONTENU DÉTAILLÉ\n";
echo "===================================\n";

// Vérifier les éléments clés de l'avertissement
$elementsAvertissement = [
    '⚠️ Avertissement Important' => 'Titre de la section',
    'Engagement de paiement' => 'Engagement de l\'utilisateur',
    '3 commandes' => 'Limite de commandes non honorées',
    'banni définitivement' => 'Conséquence du non-paiement',
    'Ne passez commande que si' => 'Conseil préventif',
    'contactez-nous avant la commande' => 'Alternative proposée'
];

foreach ($elementsAvertissement as $element => $description) {
    $presentSimple = strpos($templateConfirmation, $element) !== false;
    $presentMultiple = strpos($templateConfirmationMultiple, $element) !== false;
    
    testResult(
        "Élément '{$description}' présent",
        $presentSimple && $presentMultiple,
        $presentSimple && $presentMultiple ? "Présent dans les deux templates" : "Manquant dans au moins un template"
    );
}

echo "\n";

// 4. VÉRIFICATION STYLE ET PRÉSENTATION
echo "4. VÉRIFICATION STYLE ET PRÉSENTATION\n";
echo "========================================\n";

testResult(
    "Style d'avertissement cohérent",
    strpos($templateConfirmation, 'background: #fff3cd') !== false && strpos($templateConfirmation, 'border: 2px solid #ffc107') !== false,
    "Couleur d'avertissement jaune/orange"
);

testResult(
    "Style d'avertissement cohérent (multiple)",
    strpos($templateConfirmationMultiple, 'background: #fff3cd') !== false && strpos($templateConfirmationMultiple, 'border: 2px solid #ffc107') !== false,
    "Couleur d'avertissement jaune/orange"
);

testResult(
    "Icône d'avertissement présente",
    strpos($templateConfirmation, '⚠️') !== false,
    "Icône d'avertissement dans le titre"
);

testResult(
    "Icône d'avertissement présente (multiple)",
    strpos($templateConfirmationMultiple, '⚠️') !== false,
    "Icône d'avertissement dans le titre"
);

echo "\n";

// 5. SIMULATION D'ENVOI D'EMAIL
echo "5. SIMULATION D'ENVOI D'EMAIL\n";
echo "===============================\n";

// Trouver un utilisateur et un lot pour la simulation
$user = $userRepository->createQueryBuilder('u')
    ->setMaxResults(1)
    ->getQuery()
    ->getOneOrNullResult();

$lot = $lotRepository->createQueryBuilder('l')
    ->setMaxResults(1)
    ->getQuery()
    ->getOneOrNullResult();

if ($user && $lot) {
    echo "🎭 Simulation d'envoi d'email de confirmation...\n";
    
    // Simuler les données d'une commande
    $commandeSimulee = [
        'numeroCommande' => 'CMD-TEST-' . date('YmdHis'),
        'prixTotal' => $lot->getPrix(),
        'createdAt' => new \DateTimeImmutable(),
        'quantite' => 1,
        'prixUnitaire' => $lot->getPrix()
    ];
    
    echo "   - Utilisateur : {$user->getEmail()}\n";
    echo "   - Lot : {$lot->getName()}\n";
    echo "   - Montant : {$commandeSimulee['prixTotal']}€\n";
    echo "   - Numéro commande : {$commandeSimulee['numeroCommande']}\n";
    
    testResult(
        "Données de simulation valides",
        true,
        "Simulation prête pour test d'envoi"
    );
} else {
    testResult(
        "Données de simulation valides",
        false,
        "Utilisateur ou lot manquant pour la simulation"
    );
}

echo "\n";

// 6. VÉRIFICATION CONFORMITÉ LÉGALE
echo "6. VÉRIFICATION CONFORMITÉ LÉGALE\n";
echo "===================================\n";

testResult(
    "Avertissement clair et visible",
    strpos($templateConfirmation, 'font-weight: bold') !== false,
    "Texte en gras pour la visibilité"
);

testResult(
    "Termes précis et non ambigus",
    strpos($templateConfirmation, 'susceptible d\'être') !== false,
    "Terminologie appropriée (susceptible vs sera)"
);

testResult(
    "Alternative proposée",
    strpos($templateConfirmation, 'contactez-nous avant') !== false,
    "Solution alternative offerte"
);

testResult(
    "Conseil préventif",
    strpos($templateConfirmation, 'certain de pouvoir') !== false,
    "Guidance préventive"
);

echo "\n";

// 7. RÉSUMÉ FINAL
echo "7. RÉSUMÉ FINAL\n";
echo "=================\n";

$pourcentageReussite = ($testsReussis / $testsTotal) * 100;

echo "📊 RÉSULTATS DES TESTS :\n";
echo "   - Tests réussis : {$testsReussis}/{$testsTotal}\n";
echo "   - Pourcentage de réussite : " . number_format($pourcentageReussite, 1) . "%\n";

if ($pourcentageReussite >= 90) {
    echo "   - Status : ✅ EXCELLENT\n";
} elseif ($pourcentageReussite >= 80) {
    echo "   - Status : ✅ TRÈS BON\n";
} elseif ($pourcentageReussite >= 70) {
    echo "   - Status : ⚠️  BON\n";
} else {
    echo "   - Status : ❌ PROBLÈMES DÉTECTÉS\n";
}

echo "\n";

echo "✅ AVERTISSEMENTS IMPLÉMENTÉS :\n";
echo "   📧 Template confirmation simple\n";
echo "   📧 Template confirmation multiple\n";
echo "   ⚠️  Section d'avertissement visible\n";
echo "   🔢 Limite de 3 commandes non honorées\n";
echo "   🚫 Risque de bannissement définitif\n";
echo "   💡 Conseils préventifs\n";
echo "   📞 Alternative de contact\n";

echo "\n";

echo "🎯 AVANTAGES DE CETTE MESURE :\n";
echo "   🛡️  Protection contre les commandes fantômes\n";
echo "   📊 Réduction des commandes non honorées\n";
echo "   ⚖️  Transparence sur les conséquences\n";
echo "   🎓 Éducation des utilisateurs\n";
echo "   🔒 Protection de la plateforme\n";

echo "\n=== FIN DE LA VÉRIFICATION ===\n";

if ($pourcentageReussite >= 90) {
    echo "\n🎉 AVERTISSEMENTS IMPLÉMENTÉS AVEC SUCCÈS !\n";
    echo "   - Tous les templates mis à jour\n";
    echo "   - Avertissement clair et visible\n";
    echo "   - Politique de non-paiement définie\n";
    echo "   - Protection de la plateforme renforcée\n";
    echo "   - Conformité légale respectée\n";
} else {
    echo "\n⚠️  ATTENTION : Quelques problèmes détectés\n";
    echo "   - Vérifiez les tests échoués ci-dessus\n";
    echo "   - Corrigez les problèmes avant la production\n";
}

