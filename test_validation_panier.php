<?php

require_once __DIR__ . '/vendor/autoload.php';

echo "=== TEST VALIDATION COMMANDE PANIER ===\n\n";

// Initialiser Symfony en mode production
$kernel = new \App\Kernel('prod', false);
$kernel->boot();
$container = $kernel->getContainer();

// Récupérer l'EntityManager
$entityManager = $container->get('doctrine.orm.entity_manager');

echo "🔍 Vérification de l'environnement...\n";
$kernel = $container->get('kernel');
$environment = $kernel->getEnvironment();
$debug = $kernel->isDebug();

echo "    📋 Environnement: $environment\n";
echo "    🐛 Debug: " . ($debug ? 'ACTIVÉ' : 'DÉSACTIVÉ') . "\n";

echo "\n🛒 Test de validation du panier...\n";

try {
    // Récupérer les repositories
    $userRepository = $entityManager->getRepository('App\Entity\User');
    $lotRepository = $entityManager->getRepository('App\Entity\Lot');
    $panierRepository = $entityManager->getRepository('App\Entity\Panier');
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

    // Créer un article de panier pour le test
    $panierItem = new \App\Entity\Panier();
    $panierItem->setUser($user);
    $panierItem->setLot($lot);
    $panierItem->setQuantite(1);

    $entityManager->persist($panierItem);
    $entityManager->flush();

    echo "    ✅ Article panier créé (ID: {$panierItem->getId()})\n";

    // Simuler la validation du panier
    echo "\n🔄 Simulation de la validation...\n";

    $items = $panierRepository->findByUser($user);
    echo "    📦 Articles dans le panier: " . count($items) . "\n";

    if (empty($items)) {
        echo "    ❌ Panier vide\n";
        exit;
    }

    // Vérifier les stocks
    foreach ($items as $item) {
        echo "    🔍 Vérification stock pour " . $item->getLot()->getName() . "\n";
        echo "        Quantité demandée: " . $item->getQuantite() . "\n";
        echo "        Stock disponible: " . $item->getLot()->getQuantite() . "\n";

        if ($item->getQuantite() > $item->getLot()->getQuantite()) {
            echo "        ❌ Stock insuffisant\n";
            exit;
        }
        echo "        ✅ Stock suffisant\n";
    }

    // Créer les commandes
    echo "\n📝 Création des commandes...\n";
    $commandes = [];
    $totalGeneral = 0;

    foreach ($items as $item) {
        $commande = new \App\Entity\Commande();
        $commande->setUser($user);
        $commande->setLot($item->getLot());
        $commande->setQuantite($item->getQuantite());
        $commande->setPrixUnitaire($item->getLot()->getPrix());
        $commande->setPrixTotal($item->getTotal());
        $commande->setStatut('en_attente');
        $commande->setCreatedAt(new \DateTimeImmutable());

        $entityManager->persist($commande);
        $commandes[] = $commande;
        $totalGeneral += $item->getTotal();

        echo "    ✅ Commande créée pour " . $item->getLot()->getName() . " (ID: {$commande->getId()})\n";

        // Mettre à jour le stock du lot
        $lot = $item->getLot();
        $nouvelleQuantite = $lot->getQuantite() - $item->getQuantite();

        if ($nouvelleQuantite <= 0) {
            $lot->setQuantite(0);
            $lot->setStatut('reserve');
            $lot->setReservePar($user);
            $lot->setReserveAt(new \DateTimeImmutable());
            echo "    🔒 Lot réservé (stock à 0)\n";
        } else {
            $lot->setQuantite($nouvelleQuantite);
            echo "    📉 Stock réduit à " . $nouvelleQuantite . "\n";
        }

        $entityManager->persist($lot);

        // Supprimer l'article du panier
        $entityManager->remove($item);
        echo "    🗑️ Article supprimé du panier\n";
    }

    $entityManager->flush();
    echo "\n✅ Validation réussie !\n";
    echo "    📊 Total général: " . number_format($totalGeneral, 2) . "€\n";
    echo "    📦 Commandes créées: " . count($commandes) . "\n";

    // Nettoyer les données de test
    echo "\n🧹 Nettoyage des données de test...\n";
    foreach ($commandes as $commande) {
        $entityManager->remove($commande);
    }
    $entityManager->flush();
    echo "    ✅ Données de test supprimées\n";
} catch (Exception $e) {
    echo "    ❌ ERREUR: " . $e->getMessage() . "\n";
    echo "    📍 Fichier: " . $e->getFile() . " Ligne: " . $e->getLine() . "\n";
    echo "    🔍 Trace: " . $e->getTraceAsString() . "\n";
}

echo "\n=== FIN DU TEST ===\n";

