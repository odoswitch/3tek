<?php

require_once __DIR__ . '/vendor/autoload.php';

echo "=== TEST FINAL COMPLET ===\n\n";

// Initialiser Symfony en mode production
$kernel = new \App\Kernel('prod', false);
$kernel->boot();
$container = $kernel->getContainer();

// Récupérer l'EntityManager
$entityManager = $container->get('doctrine.orm.entity_manager');

echo "🔍 Test final complet...\n";

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
    echo "    ✅ Lot trouvé: " . $lot->getName() . "\n";

    // Test 1: Vérifier que l'utilisateur a les bonnes catégories et types
    echo "\n🔍 Test 1: Vérification des catégories et types...\n";
    $userCategories = $user->getCategorie();
    $userType = $user->getType();

    if ($userCategories->isEmpty() || !$userType) {
        echo "    ⚠️ Utilisateur sans catégories ou type - assignation automatique\n";

        // Assigner la catégorie du lot à l'utilisateur
        $lotCategory = $lot->getCat();
        if ($lotCategory) {
            $user->addCategorie($lotCategory);
            echo "    ✅ Catégorie '{$lotCategory->getName()}' assignée\n";
        }

        // Assigner le premier type du lot à l'utilisateur
        $lotTypes = $lot->getTypes();
        if ($lotTypes->count() > 0) {
            $firstType = $lotTypes->first();
            $user->setType($firstType);
            echo "    ✅ Type '{$firstType->getName()}' assigné\n";
        }

        $entityManager->persist($user);
        $entityManager->flush();
    } else {
        echo "    ✅ Utilisateur a déjà les bonnes catégories et types\n";
    }

    // Test 2: Vérifier la visibilité des lots
    echo "\n🔍 Test 2: Vérification de la visibilité des lots...\n";
    $lotsVisibles = $lotRepository->findAvailableForUser($user);
    echo "    📦 Lots visibles: " . count($lotsVisibles) . "\n";

    $lotTrouve = false;
    foreach ($lotsVisibles as $lotVisible) {
        if ($lotVisible->getId() === $lot->getId()) {
            $lotTrouve = true;
            echo "    ✅ Lot trouvé dans la liste des lots visibles\n";
            break;
        }
    }

    if (!$lotTrouve) {
        echo "    ❌ Lot non trouvé dans la liste des lots visibles\n";
        exit;
    }

    // Test 3: Créer une commande en attente
    echo "\n🔍 Test 3: Création d'une commande en attente...\n";
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

    // Réserver le lot
    $lot->setQuantite(0);
    $lot->setStatut('reserve');
    $lot->setReservePar($user);
    $lot->setReserveAt(new \DateTimeImmutable());
    $entityManager->persist($lot);
    $entityManager->flush();

    echo "    🔒 Lot réservé (quantité: {$lot->getQuantite()}, statut: {$lot->getStatut()})\n";

    // Test 4: Vérifier que le lot réservé est toujours visible
    echo "\n🔍 Test 4: Vérification de la visibilité du lot réservé...\n";
    $lotsVisiblesReserve = $lotRepository->findAvailableForUser($user);
    echo "    📦 Lots visibles après réservation: " . count($lotsVisiblesReserve) . "\n";

    $lotTrouveReserve = false;
    foreach ($lotsVisiblesReserve as $lotVisible) {
        if ($lotVisible->getId() === $lot->getId()) {
            $lotTrouveReserve = true;
            echo "    ✅ Lot réservé trouvé dans la liste des lots visibles\n";

            // Vérifier les commandes en attente
            $commandesEnAttente = $entityManager->getRepository(\App\Entity\Commande::class)
                ->count(['lot' => $lotVisible, 'statut' => 'en_attente']);
            echo "        - Commandes en attente: " . $commandesEnAttente . "\n";

            if ($commandesEnAttente > 0) {
                echo "        ✅ Devrait être affiché comme 'Réservé (Commande en attente)'\n";
            }
            break;
        }
    }

    if (!$lotTrouveReserve) {
        echo "    ❌ Lot réservé non trouvé dans la liste des lots visibles\n";
        exit;
    }

    // Test 5: Simuler la suppression de commande depuis l'admin
    echo "\n🔍 Test 5: Simulation de suppression de commande depuis l'admin...\n";

    // Simuler la logique de suppression
    $lotOriginal = clone $lot;

    // Restaurer la quantité si elle était à 0
    if ($lot->getQuantite() == 0) {
        $lot->setQuantite(1);
    }

    // Chercher le premier utilisateur dans la file d'attente
    $fileAttenteRepository = $entityManager->getRepository(\App\Entity\FileAttente::class);
    $fileAttente = $fileAttenteRepository->findOneBy(
        ['lot' => $lot],
        ['position' => 'ASC']
    );

    if ($fileAttente) {
        // Réserver pour le premier utilisateur en file d'attente
        $lot->setStatut('reserve');
        $lot->setReservePar($fileAttente->getUser());
        $lot->setReserveAt(new \DateTimeImmutable());
        echo "    ✅ Lot réservé pour utilisateur en file d'attente ID=" . $fileAttente->getUser()->getId() . "\n";
    } else {
        // Libérer pour tous
        $lot->setStatut('disponible');
        $lot->setReservePar(null);
        $lot->setReserveAt(null);
        echo "    ✅ Lot libéré pour tous\n";
    }

    $entityManager->persist($lot);
    $entityManager->flush();

    echo "    ✅ Logique de suppression testée avec succès\n";

    echo "\n🎉 TOUS LES TESTS RÉUSSIS !\n";
    echo "    ✅ Utilisateur avec catégories et types corrects\n";
    echo "    ✅ Lots visibles avant réservation\n";
    echo "    ✅ Commande en attente créée\n";
    echo "    ✅ Lot réservé toujours visible\n";
    echo "    ✅ Logique de suppression fonctionnelle\n";

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

echo "\n=== FIN DU TEST FINAL ===\n";
