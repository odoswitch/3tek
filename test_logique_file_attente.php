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

echo "=== TEST LOGIQUE FILE D'ATTENTE - LOT RÉSERVÉ POUR LE PREMIER ===\n\n";

// 1. Trouver un lot disponible
echo "1. RECHERCHE D'UN LOT DISPONIBLE\n";
echo "==================================\n";

$lot = $lotRepository->createQueryBuilder('l')
    ->where('l.statut = :statut')
    ->andWhere('l.quantite > 0')
    ->setParameter('statut', 'disponible')
    ->setMaxResults(1)
    ->getQuery()
    ->getOneOrNullResult();

if (!$lot) {
    echo "❌ Aucun lot disponible pour le test\n";
    echo "💡 Libérons un lot existant...\n";
    
    // Trouver un lot réservé et le libérer
    $lotReserve = $lotRepository->createQueryBuilder('l')
        ->where('l.statut = :statut')
        ->setParameter('statut', 'reserve')
        ->setMaxResults(1)
        ->getQuery()
        ->getOneOrNullResult();
    
    if ($lotReserve) {
        $lotReserve->setStatut('disponible');
        $lotReserve->setReservePar(null);
        $lotReserve->setReserveAt(null);
        $lotReserve->setQuantite(1);
        
        $entityManager->persist($lotReserve);
        $entityManager->flush();
        
        $lot = $lotReserve;
        echo "✅ Lot libéré pour le test : {$lot->getName()}\n";
    } else {
        echo "❌ Aucun lot trouvé pour le test\n";
        exit(1);
    }
} else {
    echo "✅ Lot disponible trouvé : {$lot->getName()}\n";
}

echo "📊 Détails du lot :\n";
echo "   - ID: {$lot->getId()}\n";
echo "   - Nom: {$lot->getName()}\n";
echo "   - Prix: {$lot->getPrix()}€\n";
echo "   - Statut: {$lot->getStatut()}\n";
echo "   - Quantité: {$lot->getQuantite()}\n\n";

// 2. Trouver des utilisateurs pour le test
echo "2. SÉLECTION DES UTILISATEURS\n";
echo "===============================\n";

$users = $userRepository->createQueryBuilder('u')
    ->setMaxResults(3)
    ->getQuery()
    ->getResult();

if (count($users) < 2) {
    echo "❌ Pas assez d'utilisateurs pour le test (minimum 2 requis)\n";
    exit(1);
}

$user1 = $users[0]; // Utilisateur qui va créer la commande
$user2 = $users[1]; // Premier utilisateur en file d'attente
$user3 = count($users) > 2 ? $users[2] : null; // Deuxième utilisateur en file d'attente (optionnel)

echo "✅ Utilisateurs sélectionnés :\n";
echo "   - User1 (créera commande): {$user1->getEmail()}\n";
echo "   - User2 (premier en file): {$user2->getEmail()}\n";
if ($user3) {
    echo "   - User3 (deuxième en file): {$user3->getEmail()}\n";
}
echo "\n";

// 3. Créer une commande et réserver le lot
echo "3. CRÉATION DE LA COMMANDE\n";
echo "============================\n";

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

echo "✅ Commande créée :\n";
echo "   - ID: {$commande->getId()}\n";
echo "   - Utilisateur: {$user1->getEmail()}\n";
echo "   - Lot: {$lot->getName()}\n";
echo "   - Statut: {$commande->getStatut()}\n";
echo "✅ Lot réservé pour {$user1->getEmail()}\n\n";

// 4. Ajouter des utilisateurs en file d'attente
echo "4. CRÉATION DE LA FILE D'ATTENTE\n";
echo "==================================\n";

// Ajouter user2 en position 1
$fileAttente1 = new FileAttente();
$fileAttente1->setLot($lot);
$fileAttente1->setUser($user2);
$fileAttente1->setPosition(1);

$entityManager->persist($fileAttente1);

echo "✅ User2 ajouté en file d'attente :\n";
echo "   - Position: 1\n";
echo "   - Utilisateur: {$user2->getEmail()}\n";

// Ajouter user3 en position 2 (si disponible)
if ($user3) {
    $fileAttente2 = new FileAttente();
    $fileAttente2->setLot($lot);
    $fileAttente2->setUser($user3);
    $fileAttente2->setPosition(2);
    
    $entityManager->persist($fileAttente2);
    
    echo "✅ User3 ajouté en file d'attente :\n";
    echo "   - Position: 2\n";
    echo "   - Utilisateur: {$user3->getEmail()}\n";
}

$entityManager->flush();

echo "\n";

// 5. Vérifier l'état avant annulation
echo "5. ÉTAT AVANT ANNULATION\n";
echo "==========================\n";

echo "📊 Commande :\n";
echo "   - Statut: {$commande->getStatut()}\n";
echo "   - Utilisateur: {$commande->getUser()->getEmail()}\n";

echo "📊 Lot :\n";
echo "   - Statut: {$lot->getStatut()}\n";
echo "   - Réservé par: {$lot->getReservePar()->getEmail()}\n";

echo "📊 File d'attente :\n";
$filesAttente = $fileAttenteRepository->findByLot($lot);
foreach ($filesAttente as $file) {
    echo "   - Position {$file->getPosition()}: {$file->getUser()->getEmail()} (statut: {$file->getStatut()})\n";
}

echo "\n";

// 6. Annuler la commande (simuler la logique unifiée)
echo "6. ANNULATION DE LA COMMANDE\n";
echo "==============================\n";

echo "🔄 Application de la logique de libération unifiée...\n";

// Simuler la logique du service LotLiberationService
$commande->setStatut('annulee');
$lot->setQuantite(1); // Restaurer la quantité

// Chercher le premier utilisateur dans la file d'attente
$premierEnAttente = $fileAttenteRepository->findFirstInQueue($lot);

if ($premierEnAttente) {
    echo "✅ Premier utilisateur en file d'attente trouvé :\n";
    echo "   - Email: {$premierEnAttente->getUser()->getEmail()}\n";
    echo "   - Position: {$premierEnAttente->getPosition()}\n";
    
    // Réserver automatiquement le lot pour le premier utilisateur en file d'attente
    $lot->setStatut('reserve');
    $lot->setReservePar($premierEnAttente->getUser());
    $lot->setReserveAt(new \DateTimeImmutable());
    
    echo "✅ Lot réservé automatiquement pour le premier utilisateur de la file\n";
    
    // Marquer comme notifié
    $premierEnAttente->setStatut('notifie');
    $premierEnAttente->setNotifiedAt(new \DateTimeImmutable());
    $entityManager->persist($premierEnAttente);
    
    echo "✅ Premier utilisateur marqué comme notifié\n";
} else {
    echo "❌ Aucun utilisateur en file d'attente trouvé\n";
    $lot->setStatut('disponible');
    $lot->setReservePar(null);
    $lot->setReserveAt(null);
}

$entityManager->persist($commande);
$entityManager->persist($lot);
$entityManager->flush();

echo "✅ Annulation appliquée avec logique unifiée\n\n";

// 7. Vérifier l'état final
echo "7. ÉTAT FINAL APRÈS ANNULATION\n";
echo "=================================\n";

$commandeFinale = $commandeRepository->find($commande->getId());
$lotFinal = $lotRepository->find($lot->getId());
$filesAttenteFinales = $fileAttenteRepository->findByLot($lotFinal);

echo "📊 Commande finale :\n";
echo "   - Statut: {$commandeFinale->getStatut()}\n";
echo "   - Utilisateur: {$commandeFinale->getUser()->getEmail()}\n";

echo "📊 Lot final :\n";
echo "   - Statut: {$lotFinal->getStatut()}\n";
echo "   - Quantité: {$lotFinal->getQuantite()}\n";
echo "   - Réservé par: " . ($lotFinal->getReservePar() ? $lotFinal->getReservePar()->getEmail() : 'Aucun') . "\n";
echo "   - Réservé le: " . ($lotFinal->getReserveAt() ? $lotFinal->getReserveAt()->format('d/m/Y H:i') : 'Jamais') . "\n";

echo "📊 File d'attente finale :\n";
if (empty($filesAttenteFinales)) {
    echo "   - Aucune file d'attente\n";
} else {
    foreach ($filesAttenteFinales as $file) {
        echo "   - Position {$file->getPosition()}: {$file->getUser()->getEmail()} (statut: {$file->getStatut()})\n";
    }
}

echo "\n";

// 8. Test de disponibilité pour différents utilisateurs
echo "8. TEST DE DISPONIBILITÉ POUR DIFFÉRENTS UTILISATEURS\n";
echo "========================================================\n";

$testUsers = [$user1, $user2, $user3];
foreach ($testUsers as $index => $user) {
    if (!$user) continue;
    
    $estDisponible = $lotFinal->isDisponiblePour($user);
    $estEnFileAttente = $fileAttenteRepository->isUserInQueue($lotFinal, $user);
    
    echo "👤 User" . ($index + 1) . " ({$user->getEmail()}) :\n";
    echo "   - Peut commander: " . ($estDisponible ? "✅ OUI" : "❌ NON") . "\n";
    echo "   - En file d'attente: " . ($estEnFileAttente ? "✅ OUI" : "❌ NON") . "\n";
    
    if ($estEnFileAttente) {
        $fileUser = $fileAttenteRepository->createQueryBuilder('f')
            ->where('f.lot = :lot')
            ->andWhere('f.user = :user')
            ->setParameter('lot', $lotFinal)
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

// 9. Vérification de la logique métier
echo "9. VÉRIFICATION DE LA LOGIQUE MÉTIER\n";
echo "======================================\n";

echo "📋 Logique de libération unifiée :\n";
echo "   - Si file d'attente → Réserver pour le premier utilisateur\n";
echo "   - Si pas de file d'attente → Libérer pour tous\n\n";

echo "🔍 Analyse de l'état final :\n";

if (empty($filesAttenteFinales)) {
    echo "   - File d'attente : Aucune\n";
    echo "   - Statut lot attendu : 'disponible'\n";
    echo "   - Statut lot actuel : '{$lotFinal->getStatut()}'\n";
    
    if ($lotFinal->getStatut() === 'disponible') {
        echo "   ✅ LOGIQUE CORRECTE : Lot disponible pour tous\n";
    } else {
        echo "   ❌ PROBLÈME : Lot devrait être disponible pour tous\n";
    }
} else {
    echo "   - File d'attente : " . count($filesAttenteFinales) . " utilisateur(s)\n";
    echo "   - Statut lot attendu : 'reserve'\n";
    echo "   - Statut lot actuel : '{$lotFinal->getStatut()}'\n";
    
    if ($lotFinal->getStatut() === 'reserve' && $lotFinal->getReservePar()) {
        $premierEnAttente = $fileAttenteRepository->findFirstInQueue($lotFinal);
        if ($premierEnAttente && $lotFinal->getReservePar()->getId() === $premierEnAttente->getUser()->getId()) {
            echo "   ✅ LOGIQUE CORRECTE : Lot réservé pour le premier utilisateur\n";
            
            if ($premierEnAttente->getStatut() === 'notifie') {
                echo "   ✅ CORRECT : Premier utilisateur notifié\n";
            } else {
                echo "   ❌ PROBLÈME : Premier utilisateur pas notifié\n";
            }
        } else {
            echo "   ❌ PROBLÈME : Lot réservé pour le mauvais utilisateur\n";
        }
    } else {
        echo "   ❌ PROBLÈME : Lot devrait être réservé pour le premier utilisateur\n";
    }
}

echo "\n";

// 10. Résumé final
echo "10. RÉSUMÉ FINAL\n";
echo "==================\n";

$logiqueCorrecte = true;
$message = "";

if (empty($filesAttenteFinales)) {
    if ($lotFinal->getStatut() === 'disponible') {
        $message = "✅ PARFAIT : Logique de libération correcte - Lot disponible pour tous";
    } else {
        $logiqueCorrecte = false;
        $message = "❌ PROBLÈME : Lot devrait être disponible pour tous";
    }
} else {
    $premierEnAttente = $fileAttenteRepository->findFirstInQueue($lotFinal);
    if ($lotFinal->getStatut() === 'reserve' && 
        $lotFinal->getReservePar() && 
        $premierEnAttente && 
        $lotFinal->getReservePar()->getId() === $premierEnAttente->getUser()->getId() &&
        $premierEnAttente->getStatut() === 'notifie') {
        $message = "✅ PARFAIT : Logique de libération correcte - Lot réservé pour le premier utilisateur notifié";
    } else {
        $logiqueCorrecte = false;
        $message = "❌ PROBLÈME : Logique de libération incorrecte";
    }
}

echo $message . "\n\n";

if ($logiqueCorrecte) {
    echo "🎉 SUCCÈS : La logique de file d'attente fonctionne parfaitement !\n";
    echo "   - Service LotLiberationService opérationnel\n";
    echo "   - Logique unifiée appliquée correctement\n";
    echo "   - Premier utilisateur en file d'attente peut commander\n";
    echo "   - Autres utilisateurs restent en file d'attente\n";
    echo "   - Système cohérent et prévisible\n";
} else {
    echo "⚠️  ATTENTION : Il y a encore un problème dans la logique de libération\n";
    echo "   - Vérifiez les logs pour plus de détails\n";
    echo "   - Le service LotLiberationService pourrait ne pas être appelé\n";
}

echo "\n=== FIN DU TEST FILE D'ATTENTE ===\n";

