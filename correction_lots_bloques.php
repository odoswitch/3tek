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

echo "=== CORRECTION DES LOTS BLOQUÉS ===\n\n";

// Trouver les lots réservés sans file d'attente
$lotsReservesSansFile = $lotRepository->createQueryBuilder('l')
    ->leftJoin('l.filesAttente', 'f')
    ->where('l.statut = :statut')
    ->andWhere('f.id IS NULL')
    ->setParameter('statut', 'reserve')
    ->getQuery()
    ->getResult();

echo "🔧 CORRECTION DES LOTS RÉSERVÉS SANS FILE D'ATTENTE\n";
echo "====================================================\n";

foreach ($lotsReservesSansFile as $lot) {
    echo "📦 Lot ID: {$lot->getId()} - {$lot->getName()}\n";
    echo "   Statut actuel: {$lot->getStatut()}\n";
    echo "   Réservé par: " . ($lot->getReservePar() ? $lot->getReservePar()->getEmail() : 'NULL') . "\n";
    
    // Libérer le lot (le rendre disponible pour tous)
    $lot->setStatut('disponible');
    $lot->setReservePar(null);
    $lot->setReserveAt(null);
    $lot->setQuantite(1);
    
    $entityManager->persist($lot);
    
    echo "   ✅ Corrigé: Statut → disponible\n";
    echo "   ✅ Corrigé: Réservé par → NULL\n";
    echo "   ✅ Corrigé: Quantité → 1\n";
    echo "\n";
}

// Sauvegarder les changements
$entityManager->flush();

echo "💾 Changements sauvegardés en base de données\n\n";

// Vérifier le résultat
echo "🔍 VÉRIFICATION APRÈS CORRECTION\n";
echo "==================================\n";

$lotsDisponibles = $lotRepository->createQueryBuilder('l')
    ->where('l.statut = :statut')
    ->setParameter('statut', 'disponible')
    ->getQuery()
    ->getResult();

echo "📦 LOTS MAINTENANT DISPONIBLES:\n";
foreach ($lotsDisponibles as $lot) {
    echo "   - ID: {$lot->getId()} - {$lot->getName()} (Quantité: {$lot->getQuantite()})\n";
}

$lotsReserves = $lotRepository->createQueryBuilder('l')
    ->where('l.statut = :statut')
    ->setParameter('statut', 'reserve')
    ->getQuery()
    ->getResult();

echo "\n📦 LOTS ENCORE RÉSERVÉS:\n";
foreach ($lotsReserves as $lot) {
    echo "   - ID: {$lot->getId()} - {$lot->getName()} (Réservé par: " . ($lot->getReservePar() ? $lot->getReservePar()->getEmail() : 'NULL') . ")\n";
}

echo "\n✅ CORRECTION TERMINÉE !\n";
echo "   - Les lots bloqués ont été libérés\n";
echo "   - Ils sont maintenant disponibles pour tous les utilisateurs\n";
echo "   - Seuls les lots avec des utilisateurs en file d'attente restent réservés\n";

