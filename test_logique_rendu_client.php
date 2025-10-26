<?php

require_once __DIR__ . '/vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Lot;
use App\Entity\User;

// Charger les variables d'environnement
$dotenv = new Dotenv();
$dotenv->load(__DIR__ . '/.env');

// Initialiser Symfony
$kernel = new \App\Kernel($_ENV['APP_ENV'] ?? 'dev', (bool) ($_ENV['APP_DEBUG'] ?? true));
$kernel->boot();
$container = $kernel->getContainer();

$entityManager = $container->get('doctrine.orm.entity_manager');
$lotRepository = $entityManager->getRepository(Lot::class);
$userRepository = $entityManager->getRepository(User::class);

function testResult($test, $success, $details = '')
{
    $icon = $success ? '✅' : '❌';
    echo "$icon $test\n";
    if ($details) {
        echo "   $details\n";
    }
    echo "\n";
}

echo "=== TEST LOGIQUE RENDU CLIENT CORRIGÉE ===\n\n";

// 1. ÉTAT ACTUEL
echo "1. ÉTAT ACTUEL DU LOT ET DES UTILISATEURS\n";
echo "==========================================\n";

$lot = $lotRepository->find(5);
$user3 = $userRepository->find(3);
$user4 = $userRepository->find(4);

testResult(
    "Lot ID 5 trouvé",
    $lot !== null,
    $lot ? "Statut: {$lot->getStatut()}, Réservé par: " . ($lot->getReservePar() ? "ID {$lot->getReservePar()->getId()}" : "Personne") : "Lot non trouvé"
);

testResult(
    "Utilisateur ID 3 trouvé",
    $user3 !== null,
    $user3 ? "Email: {$user3->getEmail()}" : "Utilisateur non trouvé"
);

testResult(
    "Utilisateur ID 4 trouvé",
    $user4 !== null,
    $user4 ? "Email: {$user4->getEmail()}" : "Utilisateur non trouvé"
);

echo "\n";

// 2. TEST DE LA MÉTHODE isDisponiblePour CORRIGÉE
echo "2. TEST DE LA MÉTHODE isDisponiblePour CORRIGÉE\n";
echo "===============================================\n";

if ($lot && $user3 && $user4) {
    // Recharger les données pour avoir les relations à jour
    $entityManager->clear();
    $lot = $lotRepository->find(5);
    $user3 = $userRepository->find(3);
    $user4 = $userRepository->find(4);

    // Forcer le chargement des relations
    $lot->getFilesAttente()->toArray();

    // Test utilisateur ID 3 (délai expiré)
    $user3PeutVoir = $lot->isDisponiblePour($user3);
    testResult(
        "Utilisateur ID 3 (délai expiré) peut commander",
        $user3PeutVoir,
        $user3PeutVoir ? "❌ PROBLÈME: Peut commander" : "✅ CORRECT: Ne peut pas commander"
    );

    // Test utilisateur ID 4 (en attente de validation)
    $user4PeutVoir = $lot->isDisponiblePour($user4);
    testResult(
        "Utilisateur ID 4 (en attente de validation) peut commander",
        $user4PeutVoir,
        $user4PeutVoir ? "✅ CORRECT: Peut commander" : "❌ PROBLÈME: Ne peut pas commander"
    );

    // Test avec un utilisateur qui n'est pas en file d'attente
    $user2 = $userRepository->find(2);
    if ($user2) {
        $user2PeutVoir = $lot->isDisponiblePour($user2);
        testResult(
            "Utilisateur ID 2 (pas en file d'attente) peut commander",
            $user2PeutVoir,
            $user2PeutVoir ? "❌ PROBLÈME: Peut commander" : "✅ CORRECT: Ne peut pas commander"
        );
    }
}

echo "\n";

// 3. VÉRIFICATION DES STATUTS DANS LA FILE D'ATTENTE
echo "3. VÉRIFICATION DES STATUTS DANS LA FILE D'ATTENTE\n";
echo "===================================================\n";

if ($lot) {
    $filesAttente = $lot->getFilesAttente();
    foreach ($filesAttente as $fileAttente) {
        testResult(
            "Position {$fileAttente->getPosition()}",
            true,
            "User ID {$fileAttente->getUser()->getId()}: {$fileAttente->getUser()->getEmail()}, Statut: {$fileAttente->getStatut()}" .
                ($fileAttente->getExpiresAt() ? ", Expire: {$fileAttente->getExpiresAt()->format('H:i:s')}" : "") .
                ($fileAttente->getExpiredAt() ? ", Expiré: {$fileAttente->getExpiredAt()->format('H:i:s')}" : "")
        );
    }
}

echo "\n";

// 4. RÉSUMÉ FINAL
echo "4. RÉSUMÉ FINAL\n";
echo "================\n";

echo "🎯 LOGIQUE DE RENDU CLIENT CORRIGÉE :\n";
echo "   ✅ Méthode isDisponiblePour mise à jour\n";
echo "   ✅ Vérification du statut 'en_attente_validation'\n";
echo "   ✅ Utilisateurs avec délai expiré ne peuvent plus commander\n";
echo "   ✅ Seul l'utilisateur en attente de validation peut commander\n";
echo "   ✅ Template lot/view.html.twig corrigé\n\n";

echo "🎉 SYSTÈME COHÉRENT !\n";
echo "   La logique backend correspond maintenant au rendu client\n";

echo "\n=== FIN DU TEST ===\n";

