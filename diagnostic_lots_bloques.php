<?php

require_once __DIR__ . '/vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Lot;
use App\Entity\FileAttente;
use App\Repository\LotRepository;
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
$fileAttenteRepository = $entityManager->getRepository(FileAttente::class);

echo "=== DIAGNOSTIC DES LOTS BLOQUÉS ===\n\n";

// 1. VÉRIFIER LES LOTS RÉSERVÉS
echo "1. LOTS RÉSERVÉS (POTENTIELLEMENT BLOQUÉS)\n";
echo "===========================================\n";

$lotsReserves = $lotRepository->createQueryBuilder('l')
    ->where('l.statut = :statut')
    ->setParameter('statut', 'reserve')
    ->getQuery()
    ->getResult();

foreach ($lotsReserves as $lot) {
    echo "📦 Lot ID: {$lot->getId()} - {$lot->getName()}\n";
    echo "   Statut: {$lot->getStatut()}\n";
    echo "   Quantité: {$lot->getQuantite()}\n";
    echo "   Réservé par: " . ($lot->getReservePar() ? $lot->getReservePar()->getEmail() : 'NULL') . "\n";
    echo "   Réservé le: " . ($lot->getReserveAt() ? $lot->getReserveAt()->format('d/m/Y H:i') : 'NULL') . "\n";
    
    // Vérifier s'il y a des utilisateurs en file d'attente
    $filesAttente = $fileAttenteRepository->createQueryBuilder('f')
        ->where('f.lot = :lot')
        ->setParameter('lot', $lot)
        ->orderBy('f.position', 'ASC')
        ->getQuery()
        ->getResult();
    
    echo "   File d'attente: " . count($filesAttente) . " utilisateur(s)\n";
    
    foreach ($filesAttente as $file) {
        echo "     - Position {$file->getPosition()}: {$file->getUser()->getEmail()} (Statut: {$file->getStatut()})\n";
    }
    
    echo "\n";
}

// 2. VÉRIFIER LES LOTS DISPONIBLES
echo "2. LOTS DISPONIBLES\n";
echo "====================\n";

$lotsDisponibles = $lotRepository->createQueryBuilder('l')
    ->where('l.statut = :statut')
    ->setParameter('statut', 'disponible')
    ->getQuery()
    ->getResult();

foreach ($lotsDisponibles as $lot) {
    echo "📦 Lot ID: {$lot->getId()} - {$lot->getName()}\n";
    echo "   Statut: {$lot->getStatut()}\n";
    echo "   Quantité: {$lot->getQuantite()}\n";
    
    // Vérifier s'il y a des utilisateurs en file d'attente
    $filesAttente = $fileAttenteRepository->createQueryBuilder('f')
        ->where('f.lot = :lot')
        ->setParameter('lot', $lot)
        ->orderBy('f.position', 'ASC')
        ->getQuery()
        ->getResult();
    
    echo "   File d'attente: " . count($filesAttente) . " utilisateur(s)\n";
    
    if (count($filesAttente) > 0) {
        echo "   ⚠️  PROBLÈME: Lot disponible mais utilisateurs en file d'attente!\n";
        foreach ($filesAttente as $file) {
            echo "     - Position {$file->getPosition()}: {$file->getUser()->getEmail()} (Statut: {$file->getStatut()})\n";
        }
    }
    
    echo "\n";
}

// 3. VÉRIFIER LES FILES D'ATTENTE ORPHELINES
echo "3. FILES D'ATTENTE ORPHELINES\n";
echo "==============================\n";

$filesOrphelines = $fileAttenteRepository->createQueryBuilder('f')
    ->leftJoin('f.lot', 'l')
    ->where('l.id IS NULL')
    ->getQuery()
    ->getResult();

if (count($filesOrphelines) > 0) {
    echo "⚠️  FILES D'ATTENTE SANS LOT ASSOCIÉ:\n";
    foreach ($filesOrphelines as $file) {
        echo "   - File ID: {$file->getId()}, User: {$file->getUser()->getEmail()}\n";
    }
} else {
    echo "✅ Aucune file d'attente orpheline trouvée\n";
}

echo "\n";

// 4. RÉSUMÉ DES PROBLÈMES
echo "4. RÉSUMÉ DES PROBLÈMES DÉTECTÉS\n";
echo "==================================\n";

$problemes = [];

// Problème 1: Lots réservés sans utilisateur en file d'attente
foreach ($lotsReserves as $lot) {
    $filesAttente = $fileAttenteRepository->createQueryBuilder('f')
        ->where('f.lot = :lot')
        ->setParameter('lot', $lot)
        ->getQuery()
        ->getResult();
    
    if (count($filesAttente) == 0) {
        $problemes[] = "Lot ID {$lot->getId()} réservé mais personne en file d'attente";
    }
}

// Problème 2: Lots disponibles avec utilisateurs en file d'attente
foreach ($lotsDisponibles as $lot) {
    $filesAttente = $fileAttenteRepository->createQueryBuilder('f')
        ->where('f.lot = :lot')
        ->setParameter('lot', $lot)
        ->getQuery()
        ->getResult();
    
    if (count($filesAttente) > 0) {
        $problemes[] = "Lot ID {$lot->getId()} disponible mais utilisateurs en file d'attente";
    }
}

if (count($problemes) > 0) {
    echo "❌ PROBLÈMES DÉTECTÉS:\n";
    foreach ($problemes as $probleme) {
        echo "   - {$probleme}\n";
    }
} else {
    echo "✅ Aucun problème détecté\n";
}

echo "\n=== FIN DU DIAGNOSTIC ===\n";

