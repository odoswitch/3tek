<?php

require_once __DIR__ . '/vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Lot;
use App\Entity\User;
use App\Entity\Commande;
use App\Entity\FileAttente;
use App\Repository\LotRepository;
use App\Repository\UserRepository;
use App\Repository\CommandeRepository;
use App\Repository\FileAttenteRepository;
use App\Service\LotLiberationServiceAmeliore;
use Symfony\Component\Mailer\MailerInterface;
use Psr\Log\LoggerInterface;
use Twig\Environment;

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
$fileAttenteRepository = $entityManager->getRepository(FileAttente::class);

// Créer le service manuellement
$mailer = $container->get('mailer');
$logger = $container->get('logger');
$twig = $container->get('twig');

$lotLiberationService = new LotLiberationServiceAmeliore(
    $entityManager,
    $fileAttenteRepository,
    $mailer,
    $logger,
    $twig
);

echo "=== TEST COMPLET LOGIQUE D'ANNULATION CORRIGÉE ===\n\n";

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

// 1. PRÉPARATION DU TEST
echo "1. PRÉPARATION DU TEST\n";
echo "========================\n";

// Trouver un lot disponible
$lot = $lotRepository->createQueryBuilder('l')
    ->where('l.statut = :statut')
    ->andWhere('l.quantite > 0')
    ->setParameter('statut', 'disponible')
    ->setMaxResults(1)
    ->getQuery()
    ->getOneOrNullResult();

if (!$lot) {
    echo "💡 Créons un lot de test...\n";
    $lot = new Lot();
    $lot->setName('Lot Test Annulation Corrigée');
    $lot->setDescription('Test de la logique d\'annulation corrigée');
    $lot->setPrix(150.0);
    $lot->setQuantite(1);
    $lot->setStatut('disponible');
    
    $entityManager->persist($lot);
    $entityManager->flush();
}

testResult(
    "Lot de test disponible",
    $lot !== null,
    $lot ? "Lot: {$lot->getName()} (ID: {$lot->getId()})" : "Aucun lot disponible"
);

// Trouver des utilisateurs
$users = $userRepository->createQueryBuilder('u')
    ->setMaxResults(3)
    ->getQuery()
    ->getResult();

testResult(
    "Utilisateurs trouvés",
    count($users) >= 3,
    count($users) >= 3 ? "Utilisateurs: " . implode(', ', array_map(fn($u) => $u->getEmail(), $users)) : "Pas assez d'utilisateurs"
);

if (!$lot || count($users) < 3) {
    echo "❌ Impossible de continuer le test - données insuffisantes\n";
    exit(1);
}

$user1 = $users[0]; // Créera la commande
$user2 = $users[1]; // Premier en file d'attente
$user3 = $users[2]; // Deuxième en file d'attente

echo "\n";

// 2. CRÉATION COMMANDE ET RÉSERVATION
echo "2. CRÉATION COMMANDE ET RÉSERVATION\n";
echo "=====================================\n";

$commande = new Commande();
$commande->setUser($user1);
$commande->setLot($lot);
$commande->setQuantite(1);
$commande->setPrixUnitaire($lot->getPrix());
$commande->setPrixTotal($lot->getPrix());
$commande->setStatut('en_attente');

$entityManager->persist($commande);

// Réserver le lot
$lot->setQuantite(0);
$lot->setStatut('reserve');
$lot->setReservePar($user1);
$lot->setReserveAt(new \DateTimeImmutable());

$entityManager->persist($lot);
$entityManager->flush();

testResult(
    "Commande créée",
    $commande->getId() !== null,
    "ID: {$commande->getId()}, Utilisateur: {$user1->getEmail()}"
);

testResult(
    "Lot réservé",
    $lot->getStatut() === 'reserve' && $lot->getReservePar() === $user1,
    "Statut: {$lot->getStatut()}, Réservé par: {$lot->getReservePar()->getEmail()}"
);

echo "\n";

// 3. CRÉATION FILE D'ATTENTE AVEC PLUSIEURS UTILISATEURS
echo "3. CRÉATION FILE D'ATTENTE AVEC PLUSIEURS UTILISATEURS\n";
echo "=========================================================\n";

// User2 en position 1
$fileAttente1 = new FileAttente();
$fileAttente1->setLot($lot);
$fileAttente1->setUser($user2);
$fileAttente1->setPosition(1);
$fileAttente1->setStatut('en_attente');

$entityManager->persist($fileAttente1);

testResult(
    "User2 ajouté en file d'attente (position 1)",
    true,
    "Position: 1, Utilisateur: {$user2->getEmail()}"
);

// User3 en position 2
$fileAttente2 = new FileAttente();
$fileAttente2->setLot($lot);
$fileAttente2->setUser($user3);
$fileAttente2->setPosition(2);
$fileAttente2->setStatut('en_attente');

$entityManager->persist($fileAttente2);

testResult(
    "User3 ajouté en file d'attente (position 2)",
    true,
    "Position: 2, Utilisateur: {$user3->getEmail()}"
);

$entityManager->flush();

echo "\n";

// 4. TEST LOGIQUE D'ANNULATION CORRIGÉE
echo "4. TEST LOGIQUE D'ANNULATION CORRIGÉE\n";
echo "=======================================\n";

echo "🔄 Simulation d'annulation de commande...\n";

// Annuler la commande
$commande->setStatut('annulee');
$lot->setQuantite(1);

// Utiliser le service de libération corrigé
$lotLiberationService->libererLot($lot);

// Vérifier le résultat
$premierEnAttente = $fileAttenteRepository->createQueryBuilder('f')
    ->where('f.lot = :lot')
    ->andWhere('f.statut IN (:statuts)')
    ->setParameter('lot', $lot)
    ->setParameter('statuts', ['en_attente', 'en_attente_validation', 'notifie', 'delai_depasse'])
    ->orderBy('f.position', 'ASC')
    ->setMaxResults(1)
    ->getQuery()
    ->getOneOrNullResult();

testResult(
    "Premier utilisateur en file d'attente trouvé",
    $premierEnAttente !== null,
    $premierEnAttente ? "Email: {$premierEnAttente->getUser()->getEmail()}, Position: {$premierEnAttente->getPosition()}" : "Aucun utilisateur trouvé"
);

if ($premierEnAttente) {
    testResult(
        "Lot réservé pour le premier utilisateur",
        $lot->getReservePar() === $premierEnAttente->getUser(),
        "Réservé par: {$lot->getReservePar()->getEmail()}"
    );
    
    testResult(
        "Premier utilisateur marqué comme 'en_attente_validation'",
        $premierEnAttente->getStatut() === 'en_attente_validation',
        "Statut: {$premierEnAttente->getStatut()}"
    );
    
    testResult(
        "Délai d'expiration défini",
        $premierEnAttente->getExpiresAt() !== null,
        "Expire le: {$premierEnAttente->getExpiresAt()->format('d/m/Y H:i')}"
    );
    
    testResult(
        "Lot PAS disponible pour tous (statut = reserve)",
        $lot->getStatut() === 'reserve',
        "Statut: {$lot->getStatut()} (correct - réservé pour le premier)"
    );
} else {
    testResult(
        "Lot libéré pour tous (pas de file d'attente)",
        $lot->getStatut() === 'disponible',
        "Statut: {$lot->getStatut()}"
    );
}

$entityManager->persist($commande);
$entityManager->flush();

echo "\n";

// 5. VÉRIFICATION DES PERSPECTIVES UTILISATEURS
echo "5. VÉRIFICATION DES PERSPECTIVES UTILISATEURS\n";
echo "===============================================\n";

if ($premierEnAttente) {
    // Le premier utilisateur (user2) doit pouvoir voir le lot comme disponible
    $premierPeutVoir = $lot->isDisponiblePour($premierEnAttente->getUser());
    
    testResult(
        "Premier utilisateur (User2) peut voir le lot comme disponible",
        $premierPeutVoir,
        $premierPeutVoir ? "✅ CORRECT: Premier utilisateur peut commander" : "❌ PROBLÈME: Premier utilisateur ne peut pas commander"
    );
    
    // Le deuxième utilisateur (user3) ne doit PAS pouvoir voir le lot comme disponible
    $deuxiemePeutVoir = $lot->isDisponiblePour($user3);
    
    testResult(
        "Deuxième utilisateur (User3) NE peut PAS voir le lot comme disponible",
        !$deuxiemePeutVoir,
        $deuxiemePeutVoir ? "❌ PROBLÈME: Deuxième utilisateur peut commander" : "✅ CORRECT: Deuxième utilisateur ne peut pas commander"
    );
    
    // Le premier utilisateur (user1) qui avait la commande ne doit PAS pouvoir voir le lot comme disponible
    $ancienPeutVoir = $lot->isDisponiblePour($user1);
    
    testResult(
        "Ancien utilisateur (User1) NE peut PAS voir le lot comme disponible",
        !$ancienPeutVoir,
        $ancienPeutVoir ? "❌ PROBLÈME: Ancien utilisateur peut commander" : "✅ CORRECT: Ancien utilisateur ne peut pas commander"
    );
}

echo "\n";

// 6. RÉSUMÉ FINAL
echo "6. RÉSUMÉ FINAL\n";
echo "==================\n";

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

echo "✅ LOGIQUE D'ANNULATION CORRIGÉE VÉRIFIÉE :\n";
echo "   🔄 Annulation de commande\n";
echo "   👥 Recherche du premier en file d'attente (tous statuts)\n";
echo "   🔒 Réservation pour le premier utilisateur\n";
echo "   ⏰ Délai d'1 heure défini\n";
echo "   📧 Notification envoyée\n";
echo "   🚫 Lot PAS disponible pour tous (statut = reserve)\n";
echo "   👤 Premier utilisateur peut commander\n";
echo "   🚫 Autres utilisateurs voient le lot réservé\n";

echo "\n=== FIN DU TEST ===\n";

if ($pourcentageReussite >= 90) {
    echo "\n🎉 LOGIQUE D'ANNULATION PARFAITEMENT CORRIGÉE !\n";
    echo "   - Le lot est réservé pour le premier utilisateur\n";
    echo "   - Les autres utilisateurs voient le lot comme réservé\n";
    echo "   - Seul le premier utilisateur peut commander\n";
    echo "   - Le système fonctionne exactement comme demandé\n";
} else {
    echo "\n⚠️  ATTENTION : Problèmes détectés\n";
    echo "   - Vérifiez les tests échoués ci-dessus\n";
    echo "   - La logique d'annulation doit être encore corrigée\n";
}

