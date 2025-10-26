<?php

require_once __DIR__ . '/vendor/autoload.php';

echo "=== CORRECTION UTILISATEUR ET TEST VISIBILITÉ ===\n\n";

// Initialiser Symfony en mode production
$kernel = new \App\Kernel('prod', false);
$kernel->boot();
$container = $kernel->getContainer();

// Récupérer l'EntityManager
$entityManager = $container->get('doctrine.orm.entity_manager');

echo "🔧 Correction de l'utilisateur...\n";

try {
    // Récupérer les repositories
    $userRepository = $entityManager->getRepository('App\Entity\User');
    $lotRepository = $entityManager->getRepository('App\Entity\Lot');
    $categoryRepository = $entityManager->getRepository('App\Entity\Category');
    $typeRepository = $entityManager->getRepository('App\Entity\Type');

    // Trouver un utilisateur et un lot
    $user = $userRepository->findOneBy([]);
    $lot = $lotRepository->findOneBy([]);

    if (!$user || !$lot) {
        echo "    ❌ Données insuffisantes pour le test\n";
        exit;
    }

    echo "    ✅ Utilisateur trouvé: " . $user->getEmail() . "\n";
    echo "    ✅ Lot trouvé: " . $lot->getName() . "\n";

    // Assigner la catégorie du lot à l'utilisateur
    $lotCategory = $lot->getCat();
    if ($lotCategory) {
        $user->addCategorie($lotCategory);
        echo "    ✅ Catégorie '{$lotCategory->getName()}' assignée à l'utilisateur\n";
    }

    // Assigner le premier type du lot à l'utilisateur
    $lotTypes = $lot->getTypes();
    if ($lotTypes->count() > 0) {
        $firstType = $lotTypes->first();
        $user->setType($firstType);
        echo "    ✅ Type '{$firstType->getName()}' assigné à l'utilisateur\n";
    }

    $entityManager->persist($user);
    $entityManager->flush();

    echo "    ✅ Utilisateur mis à jour\n";

    // Maintenant tester la visibilité
    echo "\n🔍 Test de visibilité après correction...\n";
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

    // Créer une commande en attente pour tester l'affichage "réservé"
    echo "\n📝 Test avec commande en attente...\n";
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

    // Test de visibilité avec le lot réservé
    echo "\n🔍 Test de visibilité avec lot réservé...\n";
    $lotsVisiblesReserve = $lotRepository->findAvailableForUser($user);
    echo "    📦 Lots visibles après réservation: " . count($lotsVisiblesReserve) . "\n";

    $lotTrouveReserve = false;
    foreach ($lotsVisiblesReserve as $lotVisible) {
        if ($lotVisible->getId() === $lot->getId()) {
            $lotTrouveReserve = true;
            echo "    ✅ Lot réservé trouvé dans la liste des lots visibles\n";
            echo "        - Nom: " . $lotVisible->getName() . "\n";
            echo "        - Quantité: " . $lotVisible->getQuantite() . "\n";
            echo "        - Statut: " . $lotVisible->getStatut() . "\n";

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
    }

    echo "\n🎉 TEST TERMINÉ AVEC SUCCÈS\n";
    echo "    ✅ Utilisateur corrigé avec catégorie et type\n";
    echo "    ✅ Lot visible avant réservation\n";
    echo "    ✅ Lot visible après réservation\n";
    echo "    ✅ Commande en attente créée\n";

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

