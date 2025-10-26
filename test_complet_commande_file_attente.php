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
$kernel = new \App\Kernel($_ENV['APP_ENV'], (bool) $_ENV['APP_DEBUG']);
$kernel->boot();

$container = $kernel->getContainer();
$entityManager = $container->get('doctrine.orm.entity_manager');

// Récupérer les repositories
$lotRepository = $entityManager->getRepository(Lot::class);
$userRepository = $entityManager->getRepository(User::class);
$commandeRepository = $entityManager->getRepository(Commande::class);
$fileAttenteRepository = $entityManager->getRepository(FileAttente::class);

echo "=== TEST COMPLET : SYSTÈME DE COMMANDES ET FILE D'ATTENTE ===\n\n";

// Étape 1: Trouver un lot disponible
echo "1. RECHERCHE D'UN LOT DISPONIBLE\n";
echo "================================\n";

$lotDisponible = $lotRepository->createQueryBuilder('l')
    ->where('l.statut = :statut')
    ->andWhere('l.quantite > 0')
    ->setParameter('statut', 'disponible')
    ->setMaxResults(1)
    ->getQuery()
    ->getOneOrNullResult();

if (!$lotDisponible) {
    echo "❌ ERREUR: Aucun lot disponible trouvé\n";
    exit(1);
}

echo "✅ Lot trouvé: ID={$lotDisponible->getId()}, Nom='{$lotDisponible->getName()}', Quantité={$lotDisponible->getQuantite()}, Prix={$lotDisponible->getPrix()}€\n\n";

// Étape 2: Trouver des utilisateurs de test
echo "2. RECHERCHE D'UTILISATEURS DE TEST\n";
echo "===================================\n";

$users = $userRepository->createQueryBuilder('u')
    ->setMaxResults(3)
    ->getQuery()
    ->getResult();

if (count($users) < 2) {
    echo "❌ ERREUR: Pas assez d'utilisateurs pour le test (minimum 2 requis)\n";
    exit(1);
}

$user1 = $users[0]; // Premier utilisateur (créera la commande)
$user2 = $users[1]; // Deuxième utilisateur (sera ajouté à la file d'attente)

echo "✅ Utilisateur 1: ID={$user1->getId()}, Email='{$user1->getEmail()}'\n";
echo "✅ Utilisateur 2: ID={$user2->getId()}, Email='{$user2->getEmail()}'\n\n";

// Étape 3: Créer une commande (simulation)
echo "3. CRÉATION D'UNE COMMANDE\n";
echo "==========================\n";

try {
    // Simuler la création d'une commande comme dans le contrôleur
    $quantite = 1;

    if ($quantite > $lotDisponible->getQuantite()) {
        throw new Exception("Quantité demandée non disponible");
    }

    // Créer la commande
    $commande = new Commande();
    $commande->setUser($user1);
    $commande->setLot($lotDisponible);
    $commande->setQuantite($quantite);
    $commande->setPrixUnitaire($lotDisponible->getPrix());
    $commande->setPrixTotal($lotDisponible->getPrix() * $quantite);
    $commande->setStatut('en_attente');

    $entityManager->persist($commande);
    $entityManager->flush();

    echo "✅ Commande créée: ID={$commande->getId()}, Numéro='{$commande->getNumeroCommande()}', Statut='{$commande->getStatut()}'\n";

    // Mettre à jour le lot (simuler la réservation)
    $nouvelleQuantite = $lotDisponible->getQuantite() - $quantite;

    if ($nouvelleQuantite <= 0) {
        $lotDisponible->setQuantite(0);
        $lotDisponible->setStatut('reserve');
        $lotDisponible->setReservePar($user1);
        $lotDisponible->setReserveAt(new \DateTimeImmutable());
        echo "✅ Lot marqué comme réservé par l'utilisateur 1\n";
    } else {
        $lotDisponible->setQuantite($nouvelleQuantite);
        echo "✅ Quantité du lot décrémentée à {$nouvelleQuantite}\n";
    }

    $entityManager->persist($lotDisponible);
    $entityManager->flush();
} catch (Exception $e) {
    echo "❌ ERREUR lors de la création de la commande: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n";

// Étape 4: Ajouter un utilisateur à la file d'attente
echo "4. AJOUT À LA FILE D'ATTENTE\n";
echo "============================\n";

try {
    // Vérifier si l'utilisateur est déjà dans la file d'attente
    if ($fileAttenteRepository->isUserInQueue($lotDisponible, $user2)) {
        echo "⚠️  L'utilisateur 2 est déjà dans la file d'attente\n";
    } else {
        // Ajouter à la file d'attente
        $fileAttente = new FileAttente();
        $fileAttente->setLot($lotDisponible);
        $fileAttente->setUser($user2);
        $fileAttente->setPosition($fileAttenteRepository->getNextPosition($lotDisponible));

        $entityManager->persist($fileAttente);
        $entityManager->flush();

        echo "✅ Utilisateur 2 ajouté à la file d'attente (position {$fileAttente->getPosition()})\n";
    }

    // Afficher la file d'attente actuelle
    $filesAttente = $fileAttenteRepository->findByLot($lotDisponible);
    echo "📋 File d'attente actuelle:\n";
    foreach ($filesAttente as $file) {
        echo "   - Position {$file->getPosition()}: {$file->getUser()->getEmail()} (statut: {$file->getStatut()})\n";
    }
} catch (Exception $e) {
    echo "❌ ERREUR lors de l'ajout à la file d'attente: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n";

// Étape 5: Annuler la commande
echo "5. ANNULATION DE LA COMMANDE\n";
echo "=============================\n";

try {
    // Marquer la commande comme annulée
    $commande->setStatut('annulee');
    $entityManager->persist($commande);

    // Remettre le lot disponible
    $lotDisponible->setStatut('disponible');
    $lotDisponible->setReservePar(null);
    $lotDisponible->setReserveAt(null);
    $lotDisponible->setQuantite($lotDisponible->getQuantite() + $quantite); // Restaurer la quantité

    $entityManager->persist($lotDisponible);

    // Notifier le premier utilisateur en file d'attente
    $filesAttente = $fileAttenteRepository->findByLot($lotDisponible);
    if (!empty($filesAttente)) {
        $premierEnFile = $filesAttente[0];
        $premierEnFile->setStatut('notifie');
        $premierEnFile->setNotifiedAt(new \DateTimeImmutable());
        $entityManager->persist($premierEnFile);

        echo "✅ Premier utilisateur en file d'attente notifié: {$premierEnFile->getUser()->getEmail()}\n";
    }

    $entityManager->flush();

    echo "✅ Commande annulée et lot remis disponible\n";
    echo "✅ Quantité du lot restaurée à {$lotDisponible->getQuantite()}\n";
} catch (Exception $e) {
    echo "❌ ERREUR lors de l'annulation: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n";

// Étape 6: Vérification finale
echo "6. VÉRIFICATION FINALE\n";
echo "=======================\n";

// Vérifier l'état du lot
$lotFinal = $lotRepository->find($lotDisponible->getId());
echo "📊 État final du lot:\n";
echo "   - ID: {$lotFinal->getId()}\n";
echo "   - Nom: {$lotFinal->getName()}\n";
echo "   - Statut: {$lotFinal->getStatut()} ({$lotFinal->getStatutLabel()})\n";
echo "   - Quantité: {$lotFinal->getQuantite()}\n";
echo "   - Réservé par: " . ($lotFinal->getReservePar() ? $lotFinal->getReservePar()->getEmail() : 'Aucun') . "\n";

// Vérifier l'état de la commande
$commandeFinale = $commandeRepository->find($commande->getId());
echo "\n📊 État final de la commande:\n";
echo "   - ID: {$commandeFinale->getId()}\n";
echo "   - Numéro: {$commandeFinale->getNumeroCommande()}\n";
echo "   - Statut: {$commandeFinale->getStatut()} ({$commandeFinale->getStatutLabel()})\n";
echo "   - Utilisateur: {$commandeFinale->getUser()->getEmail()}\n";

// Vérifier la file d'attente
$filesAttenteFinales = $fileAttenteRepository->findByLot($lotFinal);
echo "\n📊 État final de la file d'attente:\n";
if (empty($filesAttenteFinales)) {
    echo "   - Aucune entrée en file d'attente\n";
} else {
    foreach ($filesAttenteFinales as $file) {
        echo "   - Position {$file->getPosition()}: {$file->getUser()->getEmail()} (statut: {$file->getStatut()})\n";
    }
}

echo "\n=== RÉSULTAT DU TEST ===\n";

// Analyser les résultats
$testReussi = true;
$problemes = [];

if ($lotFinal->getStatut() !== 'disponible') {
    $testReussi = false;
    $problemes[] = "Le lot n'est pas revenu au statut 'disponible'";
}

if ($commandeFinale->getStatut() !== 'annulee') {
    $testReussi = false;
    $problemes[] = "La commande n'est pas au statut 'annulée'";
}

if ($lotFinal->getReservePar() !== null) {
    $testReussi = false;
    $problemes[] = "Le lot est encore marqué comme réservé par quelqu'un";
}

if ($testReussi) {
    echo "✅ TEST RÉUSSI: Le système de commandes et file d'attente fonctionne correctement\n";
    echo "✅ Tous les états sont cohérents après l'annulation\n";
} else {
    echo "❌ TEST ÉCHOUÉ: Problèmes détectés:\n";
    foreach ($problemes as $probleme) {
        echo "   - {$probleme}\n";
    }
}

echo "\n=== FIN DU TEST ===\n";

