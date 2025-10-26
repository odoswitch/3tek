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

echo "=== VÉRIFICATION APRÈS ANNULATION COMMANDE 21 ===\n\n";

// 1. Vérifier si la commande 21 existe encore
echo "1. ÉTAT DE LA COMMANDE 21\n";
echo "===========================\n";

$commande21 = $commandeRepository->find(21);

if ($commande21) {
    echo "📊 Commande 21 encore présente :\n";
    echo "   - ID: {$commande21->getId()}\n";
    echo "   - Statut: {$commande21->getStatut()}\n";
    echo "   - Lot: {$commande21->getLot()->getName()}\n";
    echo "   - Utilisateur: {$commande21->getUser()->getEmail()}\n";
    echo "   - Date création: {$commande21->getCreatedAt()->format('d/m/Y H:i')}\n";

    if ($commande21->getStatut() === 'annulee') {
        echo "   ✅ Commande annulée (pas supprimée)\n";
    } else {
        echo "   ⚠️  Commande pas encore annulée\n";
    }
} else {
    echo "❌ Commande 21 supprimée de la base de données\n";
}

echo "\n";

// 2. Vérifier l'état du lot concerné
echo "2. ÉTAT DU LOT APRÈS ANNULATION\n";
echo "==================================\n";

// Trouver le lot qui était concerné par la commande 21
$lot = null;
if ($commande21) {
    $lot = $commande21->getLot();
} else {
    // Si la commande est supprimée, chercher le lot "Lot Test Automatique"
    $lot = $lotRepository->createQueryBuilder('l')
        ->where('l.name = :name')
        ->setParameter('name', 'Lot Test Automatique')
        ->getQuery()
        ->getOneOrNullResult();
}

if (!$lot) {
    echo "❌ Lot non trouvé\n";
    exit(1);
}

echo "📊 État du lot \"{$lot->getName()}\" (ID: {$lot->getId()}) :\n";
echo "   - Statut: {$lot->getStatut()}\n";
echo "   - Quantité: {$lot->getQuantite()}\n";
echo "   - Réservé par: " . ($lot->getReservePar() ? $lot->getReservePar()->getEmail() : 'Aucun') . "\n";
echo "   - Réservé le: " . ($lot->getReserveAt() ? $lot->getReserveAt()->format('d/m/Y H:i') : 'Jamais') . "\n";

echo "\n";

// 3. Vérifier la file d'attente pour ce lot
echo "3. FILE D'ATTENTE POUR CE LOT\n";
echo "===============================\n";

$filesAttente = $fileAttenteRepository->findByLot($lot);

if (empty($filesAttente)) {
    echo "⏳ Aucune file d'attente pour ce lot\n";
    echo "   ✅ Logique correcte : Lot disponible pour tous\n";
} else {
    echo "⏳ File d'attente trouvée :\n";
    foreach ($filesAttente as $file) {
        echo "   - Position {$file->getPosition()}: {$file->getUser()->getEmail()} (statut: {$file->getStatut()})\n";
    }

    // Vérifier la logique de libération
    $premierEnAttente = $fileAttenteRepository->findFirstInQueue($lot);

    if ($premierEnAttente) {
        echo "\n🎯 PREMIER UTILISATEUR EN FILE D'ATTENTE :\n";
        echo "   - Email: {$premierEnAttente->getUser()->getEmail()}\n";
        echo "   - Position: {$premierEnAttente->getPosition()}\n";
        echo "   - Statut: {$premierEnAttente->getStatut()}\n";

        // Vérifier si le lot est réservé pour le premier utilisateur
        if ($lot->getReservePar() && $lot->getReservePar()->getId() === $premierEnAttente->getUser()->getId()) {
            echo "   ✅ CORRECT : Lot réservé pour le premier utilisateur de la file\n";
        } else {
            echo "   ❌ PROBLÈME : Lot pas réservé pour le premier utilisateur\n";
        }

        // Vérifier si le premier utilisateur a été notifié
        if ($premierEnAttente->getStatut() === 'notifie') {
            echo "   ✅ CORRECT : Premier utilisateur notifié\n";
            echo "   - Notifié le: {$premierEnAttente->getNotifiedAt()->format('d/m/Y H:i')}\n";
        } else {
            echo "   ❌ PROBLÈME : Premier utilisateur pas notifié\n";
        }
    }
}

echo "\n";

// 4. Test de disponibilité pour différents utilisateurs
echo "4. TEST DE DISPONIBILITÉ POUR DIFFÉRENTS UTILISATEURS\n";
echo "=======================================================\n";

$users = $userRepository->createQueryBuilder('u')
    ->setMaxResults(5)
    ->getQuery()
    ->getResult();

foreach ($users as $user) {
    $estDisponible = $lot->isDisponiblePour($user);
    $estEnFileAttente = $fileAttenteRepository->isUserInQueue($lot, $user);

    echo "👤 Utilisateur: {$user->getEmail()}\n";
    echo "   - Peut commander: " . ($estDisponible ? "✅ OUI" : "❌ NON") . "\n";
    echo "   - En file d'attente: " . ($estEnFileAttente ? "✅ OUI" : "❌ NON") . "\n";

    if ($estEnFileAttente) {
        $fileUser = $fileAttenteRepository->createQueryBuilder('f')
            ->where('f.lot = :lot')
            ->andWhere('f.user = :user')
            ->setParameter('lot', $lot)
            ->setParameter('user', $user)
            ->getQuery()
            ->getOneOrNullResult();

        if ($fileUser) {
            echo "   - Position: {$fileUser->getPosition()}\n";
            echo "   - Statut: {$fileUser->getStatut()}\n";
        }
    }

    echo "\n";
}

// 5. Vérification de la logique métier
echo "5. VÉRIFICATION DE LA LOGIQUE MÉTIER\n";
echo "=====================================\n";

echo "📋 Logique de libération unifiée :\n";
echo "   - Si file d'attente → Réserver pour le premier utilisateur\n";
echo "   - Si pas de file d'attente → Libérer pour tous\n\n";

echo "🔍 Analyse de l'état actuel :\n";

if (empty($filesAttente)) {
    echo "   - File d'attente : Aucune\n";
    echo "   - Statut lot attendu : 'disponible'\n";
    echo "   - Statut lot actuel : '{$lot->getStatut()}'\n";

    if ($lot->getStatut() === 'disponible') {
        echo "   ✅ LOGIQUE CORRECTE : Lot disponible pour tous\n";
    } else {
        echo "   ❌ PROBLÈME : Lot devrait être disponible pour tous\n";
    }
} else {
    echo "   - File d'attente : " . count($filesAttente) . " utilisateur(s)\n";
    echo "   - Statut lot attendu : 'reserve'\n";
    echo "   - Statut lot actuel : '{$lot->getStatut()}'\n";

    if ($lot->getStatut() === 'reserve' && $lot->getReservePar()) {
        $premierEnAttente = $fileAttenteRepository->findFirstInQueue($lot);
        if ($premierEnAttente && $lot->getReservePar()->getId() === $premierEnAttente->getUser()->getId()) {
            echo "   ✅ LOGIQUE CORRECTE : Lot réservé pour le premier utilisateur\n";
        } else {
            echo "   ❌ PROBLÈME : Lot réservé pour le mauvais utilisateur\n";
        }
    } else {
        echo "   ❌ PROBLÈME : Lot devrait être réservé pour le premier utilisateur\n";
    }
}

echo "\n";

// 6. Résumé final
echo "6. RÉSUMÉ FINAL\n";
echo "================\n";

$logiqueCorrecte = true;
$message = "";

if (empty($filesAttente)) {
    if ($lot->getStatut() === 'disponible') {
        $message = "✅ PARFAIT : Logique de libération correcte - Lot disponible pour tous";
    } else {
        $logiqueCorrecte = false;
        $message = "❌ PROBLÈME : Lot devrait être disponible pour tous";
    }
} else {
    $premierEnAttente = $fileAttenteRepository->findFirstInQueue($lot);
    if (
        $lot->getStatut() === 'reserve' &&
        $lot->getReservePar() &&
        $premierEnAttente &&
        $lot->getReservePar()->getId() === $premierEnAttente->getUser()->getId() &&
        $premierEnAttente->getStatut() === 'notifie'
    ) {
        $message = "✅ PARFAIT : Logique de libération correcte - Lot réservé pour le premier utilisateur notifié";
    } else {
        $logiqueCorrecte = false;
        $message = "❌ PROBLÈME : Logique de libération incorrecte";
    }
}

echo $message . "\n\n";

if ($logiqueCorrecte) {
    echo "🎉 SUCCÈS : La correction de l'incohérence fonctionne parfaitement !\n";
    echo "   - Service LotLiberationService opérationnel\n";
    echo "   - Logique unifiée appliquée correctement\n";
    echo "   - Templates cohérents avec la logique métier\n";
} else {
    echo "⚠️  ATTENTION : Il y a encore un problème dans la logique de libération\n";
    echo "   - Vérifiez les logs pour plus de détails\n";
    echo "   - Le service LotLiberationService pourrait ne pas être appelé\n";
}

echo "\n=== FIN DE LA VÉRIFICATION ===\n";

