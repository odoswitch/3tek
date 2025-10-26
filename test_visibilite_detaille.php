<?php

require_once __DIR__ . '/vendor/autoload.php';

echo "=== TEST DÉTAILLÉ VISIBILITÉ LOTS ===\n\n";

// Initialiser Symfony en mode production
$kernel = new \App\Kernel('prod', false);
$kernel->boot();
$container = $kernel->getContainer();

// Récupérer l'EntityManager
$entityManager = $container->get('doctrine.orm.entity_manager');

echo "🔍 Test détaillé de visibilité des lots...\n";

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

    // Analyser les catégories et types
    echo "\n🔍 Analyse des catégories et types...\n";
    echo "    👤 Utilisateur:\n";
    echo "        - ID: " . $user->getId() . "\n";
    echo "        - Vérifié: " . ($user->isVerified() ? 'OUI' : 'NON') . "\n";
    echo "        - Catégories: ";
    foreach ($user->getCategorie() as $cat) {
        echo $cat->getName() . " (ID:" . $cat->getId() . ") ";
    }
    echo "\n";
    echo "        - Type: " . ($user->getType() ? $user->getType()->getName() . " (ID:" . $user->getType()->getId() . ")" : 'AUCUN') . "\n";

    echo "    📦 Lot:\n";
    echo "        - ID: " . $lot->getId() . "\n";
    echo "        - Catégorie: " . ($lot->getCat() ? $lot->getCat()->getName() . " (ID:" . $lot->getCat()->getId() . ")" : 'AUCUNE') . "\n";
    echo "        - Types: ";
    foreach ($lot->getTypes() as $type) {
        echo $type->getName() . " (ID:" . $type->getId() . ") ";
    }
    echo "\n";

    // Test de compatibilité
    echo "\n🔍 Test de compatibilité...\n";
    $userCategories = $user->getCategorie();
    $userType = $user->getType();
    $lotCategory = $lot->getCat();
    $lotTypes = $lot->getTypes();

    $categoryMatch = false;
    $typeMatch = false;

    foreach ($userCategories as $userCat) {
        if ($userCat->getId() === $lotCategory->getId()) {
            $categoryMatch = true;
            break;
        }
    }

    if ($userType) {
        foreach ($lotTypes as $lotType) {
            if ($lotType->getId() === $userType->getId()) {
                $typeMatch = true;
                break;
            }
        }
    }

    echo "    ✅ Correspondance catégorie: " . ($categoryMatch ? 'OUI' : 'NON') . "\n";
    echo "    ✅ Correspondance type: " . ($typeMatch ? 'OUI' : 'NON') . "\n";

    if (!$categoryMatch || !$typeMatch) {
        echo "    ❌ Le lot n'est pas compatible avec l'utilisateur\n";
        echo "    💡 Solution: Créer un lot compatible ou modifier l'utilisateur\n";
        exit;
    }

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

    // Test de visibilité avec la méthode findAvailableForUser
    echo "\n🔍 Test de visibilité avec findAvailableForUser...\n";
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

        // Debug: vérifier tous les lots disponibles
        echo "\n🔍 Debug: Tous les lots disponibles...\n";
        foreach ($lotsVisibles as $lotVisible) {
            echo "        - Lot ID: " . $lotVisible->getId() . ", Nom: " . $lotVisible->getName() . ", Quantité: " . $lotVisible->getQuantite() . "\n";
        }
    }

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

