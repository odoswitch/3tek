<?php

require_once __DIR__ . '/vendor/autoload.php';

echo "=== TEST VISIBILITÉ LOTS AVEC COMMANDES EN ATTENTE ===\n\n";

// Initialiser Symfony en mode production
$kernel = new \App\Kernel('prod', false);
$kernel->boot();
$container = $kernel->getContainer();

// Récupérer l'EntityManager
$entityManager = $container->get('doctrine.orm.entity_manager');

echo "🔍 Test de visibilité des lots...\n";

try {
    // Récupérer les repositories
    $userRepository = $entityManager->getRepository('App\Entity\User');
    $lotRepository = $entityManager->getRepository('App\Entity\Lot');
    $commandeRepository = $entityManager->getRepository('App\Entity\Commande');

    // Trouver un utilisateur et un lot
    $user = $userRepository->findOneBy([]);
    $lot = $lotRepository->findOneBy([]);

    if (!$user || !$lot) {
        echo "    ❌ Données insuffisantes pour le test\n";
        exit;
    }

    echo "    ✅ Utilisateur trouvé: " . $user->getEmail() . "\n";
    echo "    ✅ Lot trouvé: " . $lot->getName() . " (quantité: " . $lot->getQuantite() . ")\n";

    // Créer une commande en attente pour ce lot
    echo "\n📝 Création d'une commande en attente...\n";
    $commande = new \App\Entity\Commande();
    $commande->setUser($user);
    $commande->setLot($lot);
    $commande->setQuantite(1);
    $commande->setPrixUnitaire($lot->getPrix());
    $commande->setPrixTotal($lot->getPrix());
    $commande->setStatut('en_attente');
    $commande->setCreatedAt(new \DateTimeImmutable());

    $entityManager->persist($commande);
    $entityManager->flush();

    echo "    ✅ Commande créée (ID: {$commande->getId()}, Statut: {$commande->getStatut()})\n";

    // Réserver le lot (quantité à 0)
    $lot->setQuantite(0);
    $lot->setStatut('reserve');
    $lot->setReservePar($user);
    $lot->setReserveAt(new \DateTimeImmutable());
    $entityManager->persist($lot);
    $entityManager->flush();

    echo "    🔒 Lot réservé (quantité: {$lot->getQuantite()}, statut: {$lot->getStatut()})\n";

    // Test 1: Vérifier que le lot est visible avec la nouvelle logique
    echo "\n🔍 Test 1: Vérification de la visibilité du lot...\n";
    $lotsVisibles = $lotRepository->findAvailableForUser($user);
    echo "    📦 Lots visibles pour l'utilisateur: " . count($lotsVisibles) . "\n";

    $lotTrouve = false;
    foreach ($lotsVisibles as $lotVisible) {
        if ($lotVisible->getId() === $lot->getId()) {
            $lotTrouve = true;
            echo "    ✅ Lot trouvé dans la liste des lots visibles\n";
            echo "        - Nom: " . $lotVisible->getName() . "\n";
            echo "        - Quantité: " . $lotVisible->getQuantite() . "\n";
            echo "        - Statut: " . $lotVisible->getStatut() . "\n";
            break;
        }
    }

    if (!$lotTrouve) {
        echo "    ❌ Lot non trouvé dans la liste des lots visibles\n";
    }

    // Test 2: Vérifier les commandes en attente
    echo "\n🔍 Test 2: Vérification des commandes en attente...\n";
    $commandesEnAttente = $commandeRepository->count(['lot' => $lot, 'statut' => 'en_attente']);
    echo "    📦 Commandes en attente pour ce lot: " . $commandesEnAttente . "\n";

    if ($commandesEnAttente > 0) {
        echo "    ✅ Commande en attente détectée\n";
    } else {
        echo "    ❌ Aucune commande en attente détectée\n";
    }

    // Test 3: Simuler l'affichage dans le contrôleur
    echo "\n🔍 Test 3: Simulation de l'affichage dans le contrôleur...\n";
    foreach ($lotsVisibles as $lotItem) {
        $commandesEnAttenteCount = $entityManager->getRepository(\App\Entity\Commande::class)
            ->count(['lot' => $lotItem, 'statut' => 'en_attente']);
        $lotItem->commandesEnAttente = $commandesEnAttenteCount;

        if ($lotItem->getId() === $lot->getId()) {
            echo "    ✅ Lot avec commandes en attente: " . $commandesEnAttenteCount . "\n";
            echo "        - Devrait être affiché comme 'Réservé (Commande en attente)'\n";
        }
    }

    echo "\n🎉 TESTS TERMINÉS\n";
    echo "    ✅ Commande en attente créée\n";
    echo "    ✅ Lot réservé\n";
    echo "    ✅ Visibilité du lot testée\n";
    echo "    ✅ Commandes en attente comptées\n";

    // Nettoyer les données de test
    echo "\n🧹 Nettoyage des données de test...\n";
    $entityManager->remove($commande);
    $lot->setQuantite(1);
    $lot->setStatut('disponible');
    $lot->setReservePar(null);
    $lot->setReserveAt(null);
    $entityManager->persist($lot);
    $entityManager->flush();
    echo "    ✅ Données de test supprimées\n";
} catch (Exception $e) {
    echo "    ❌ ERREUR: " . $e->getMessage() . "\n";
    echo "    📍 Fichier: " . $e->getFile() . " Ligne: " . $e->getLine() . "\n";
    echo "    🔍 Trace: " . $e->getTraceAsString() . "\n";
}

echo "\n=== FIN DU TEST ===\n";

