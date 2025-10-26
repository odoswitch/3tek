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

echo "=== GUIDE POUR TESTER LA SUPPRESSION DE COMMANDE ===\n\n";

// 1. Lister les commandes existantes
echo "1. COMMANDES EXISTANTES\n";
echo "========================\n";

$commandes = $commandeRepository->createQueryBuilder('c')
    ->orderBy('c.createdAt', 'DESC')
    ->setMaxResults(10)
    ->getQuery()
    ->getResult();

if (empty($commandes)) {
    echo "❌ Aucune commande trouvée\n";
    echo "\n💡 Pour créer une commande de test, exécutez d'abord le script de test complet\n";
    exit(1);
}

echo "📋 Commandes disponibles pour suppression :\n\n";

foreach ($commandes as $commande) {
    $lot = $commande->getLot();
    $user = $commande->getUser();

    echo "🆔 ID: {$commande->getId()}\n";
    echo "   📦 Lot: {$lot->getName()}\n";
    echo "   👤 Utilisateur: {$user->getEmail()}\n";
    echo "   📅 Date: {$commande->getCreatedAt()->format('d/m/Y H:i')}\n";
    echo "   📊 Statut: {$commande->getStatut()}\n";
    echo "   💰 Prix: {$commande->getPrixTotal()}€\n";

    // Vérifier s'il y a une file d'attente pour ce lot
    $filesAttente = $fileAttenteRepository->findByLot($lot);
    if (!empty($filesAttente)) {
        echo "   ⏳ File d'attente: " . count($filesAttente) . " utilisateur(s)\n";
        foreach ($filesAttente as $file) {
            echo "      - Position {$file->getPosition()}: {$file->getUser()->getEmail()} (statut: {$file->getStatut()})\n";
        }
    } else {
        echo "   ⏳ File d'attente: Aucune\n";
    }

    echo "   🎯 Recommandation: ";

    // Recommandations selon le statut
    if ($commande->getStatut() === 'en_attente') {
        echo "✅ IDÉAL - Commande en attente, test parfait\n";
    } elseif ($commande->getStatut() === 'validee') {
        echo "✅ BON - Commande validée, test de suppression\n";
    } elseif ($commande->getStatut() === 'annulee') {
        echo "⚠️  DÉJÀ ANNULÉE - Pas de test nécessaire\n";
    } else {
        echo "❓ STATUT INCONNU - À vérifier\n";
    }

    echo "\n";
}

// 2. Recommandations pour le test
echo "2. RECOMMANDATIONS POUR LE TEST\n";
echo "=================================\n";

echo "🎯 COMMANDES IDÉALES À SUPPRIMER :\n\n";

$commandesIdeales = array_filter($commandes, function ($commande) {
    return in_array($commande->getStatut(), ['en_attente', 'validee']);
});

if (empty($commandesIdeales)) {
    echo "❌ Aucune commande idéale trouvée\n";
    echo "\n💡 Créons une commande de test...\n";

    // Créer une commande de test
    $lot = $lotRepository->createQueryBuilder('l')
        ->where('l.statut = :statut')
        ->andWhere('l.quantite > 0')
        ->setParameter('statut', 'disponible')
        ->setMaxResults(1)
        ->getQuery()
        ->getOneOrNullResult();

    if (!$lot) {
        echo "❌ Aucun lot disponible pour créer une commande de test\n";
        exit(1);
    }

    $user = $userRepository->createQueryBuilder('u')
        ->setMaxResults(1)
        ->getQuery()
        ->getOneOrNullResult();

    if (!$user) {
        echo "❌ Aucun utilisateur trouvé pour créer une commande de test\n";
        exit(1);
    }

    // Créer la commande de test
    $commandeTest = new Commande();
    $commandeTest->setUser($user);
    $commandeTest->setLot($lot);
    $commandeTest->setQuantite(1);
    $commandeTest->setPrixUnitaire($lot->getPrix());
    $commandeTest->setPrixTotal($lot->getPrix());
    $commandeTest->setStatut('en_attente');

    $entityManager->persist($commandeTest);

    // Réserver le lot
    $lot->setQuantite(0);
    $lot->setStatut('reserve');
    $lot->setReservePar($user);
    $lot->setReserveAt(new \DateTimeImmutable());

    $entityManager->persist($lot);
    $entityManager->flush();

    echo "✅ Commande de test créée :\n";
    echo "   🆔 ID: {$commandeTest->getId()}\n";
    echo "   📦 Lot: {$lot->getName()}\n";
    echo "   👤 Utilisateur: {$user->getEmail()}\n";
    echo "   📊 Statut: {$commandeTest->getStatut()}\n";

    $commandeIdeale = $commandeTest;
} else {
    $commandeIdeale = $commandesIdeales[0];
    echo "✅ Commande recommandée trouvée :\n";
    echo "   🆔 ID: {$commandeIdeale->getId()}\n";
    echo "   📦 Lot: {$commandeIdeale->getLot()->getName()}\n";
    echo "   👤 Utilisateur: {$commandeIdeale->getUser()->getEmail()}\n";
    echo "   📊 Statut: {$commandeIdeale->getStatut()}\n";
}

echo "\n";

// 3. Instructions pour le test
echo "3. INSTRUCTIONS POUR LE TEST\n";
echo "==============================\n";

echo "🔧 ÉTAPES À SUIVRE :\n\n";

echo "1️⃣  Créer une file d'attente (optionnel mais recommandé) :\n";
echo "   - Ajouter un autre utilisateur en file d'attente pour le même lot\n";
echo "   - Cela permettra de tester la logique de libération complète\n\n";

echo "2️⃣  Supprimer la commande via l'interface admin :\n";
echo "   - Aller sur http://localhost:8080/admin\n";
echo "   - Naviguer vers 'Commandes'\n";
echo "   - Trouver la commande ID: {$commandeIdeale->getId()}\n";
echo "   - Cliquer sur 'Supprimer'\n\n";

echo "3️⃣  Vérifier les résultats :\n";
echo "   - Le lot doit être libéré selon la logique unifiée\n";
echo "   - Si file d'attente : lot réservé pour le premier utilisateur\n";
echo "   - Si pas de file d'attente : lot disponible pour tous\n";
echo "   - Email de notification envoyé (si file d'attente)\n\n";

// 4. Script de vérification
echo "4. SCRIPT DE VÉRIFICATION\n";
echo "===========================\n";

echo "📝 Après suppression, exécutez ce script pour vérifier :\n\n";

echo "```php\n";
echo "<?php\n";
echo "// Vérifier l'état après suppression\n";
echo "\$commande = \$commandeRepository->find({$commandeIdeale->getId()});\n";
echo "if (\$commande) {\n";
echo "    echo \"❌ Commande encore présente\\n\";\n";
echo "} else {\n";
echo "    echo \"✅ Commande supprimée\\n\";\n";
echo "}\n";
echo "\n";
echo "\$lot = \$lotRepository->find({$commandeIdeale->getLot()->getId()});\n";
echo "echo \"📊 État du lot après suppression :\\n\";\n";
echo "echo \"   - Statut: {\$lot->getStatut()}\\n\";\n";
echo "echo \"   - Quantité: {\$lot->getQuantite()}\\n\";\n";
echo "echo \"   - Réservé par: \" . (\$lot->getReservePar() ? \$lot->getReservePar()->getEmail() : 'Aucun') . \"\\n\";\n";
echo "\n";
echo "\$filesAttente = \$fileAttenteRepository->findByLot(\$lot);\n";
echo "if (!empty(\$filesAttente)) {\n";
echo "    echo \"⏳ File d'attente :\\n\";\n";
echo "    foreach (\$filesAttente as \$file) {\n";
echo "        echo \"   - Position {\$file->getPosition()}: {\$file->getUser()->getEmail()} (statut: {\$file->getStatut()})\\n\";\n";
echo "    }\n";
echo "} else {\n";
echo "    echo \"⏳ Aucune file d'attente\\n\";\n";
echo "}\n";
echo "```\n\n";

// 5. Cas de test spécifiques
echo "5. CAS DE TEST SPÉCIFIQUES\n";
echo "===========================\n";

echo "🎯 CAS 1 - Suppression avec file d'attente :\n";
echo "   - Créer une commande\n";
echo "   - Ajouter un utilisateur en file d'attente\n";
echo "   - Supprimer la commande\n";
echo "   - Vérifier : lot réservé pour le premier en file d'attente\n";
echo "   - Vérifier : email de notification envoyé\n\n";

echo "🎯 CAS 2 - Suppression sans file d'attente :\n";
echo "   - Créer une commande\n";
echo "   - Supprimer la commande\n";
echo "   - Vérifier : lot disponible pour tous\n";
echo "   - Vérifier : aucun email envoyé\n\n";

echo "🎯 CAS 3 - Suppression avec plusieurs utilisateurs en file :\n";
echo "   - Créer une commande\n";
echo "   - Ajouter 2-3 utilisateurs en file d'attente\n";
echo "   - Supprimer la commande\n";
echo "   - Vérifier : lot réservé pour le premier seulement\n";
echo "   - Vérifier : autres utilisateurs restent en file d'attente\n\n";

echo "=== FIN DU GUIDE ===\n";

echo "\n💡 RÉSUMÉ :\n";
echo "   - Commande recommandée à supprimer : ID {$commandeIdeale->getId()}\n";
echo "   - Statut actuel : {$commandeIdeale->getStatut()}\n";
echo "   - Lot concerné : {$commandeIdeale->getLot()->getName()}\n";
echo "   - Utilisateur : {$commandeIdeale->getUser()->getEmail()}\n";
echo "   - Testez via l'interface admin ou directement en base\n";

