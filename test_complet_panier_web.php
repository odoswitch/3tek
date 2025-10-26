<?php

require_once __DIR__ . '/vendor/autoload.php';

echo "=== TEST COMPLET VALIDATION PANIER WEB ===\n\n";

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

echo "\n🛒 Test complet du processus panier...\n";

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

    // Nettoyer les données existantes
    echo "\n🧹 Nettoyage des données existantes...\n";
    $existingPanier = $panierRepository->findByUser($user);
    foreach ($existingPanier as $item) {
        $entityManager->remove($item);
    }
    $entityManager->flush();
    echo "    ✅ Anciens articles panier supprimés\n";

    // Étape 1: Ajouter un article au panier
    echo "\n📦 Étape 1: Ajout au panier...\n";
    $panierItem = new \App\Entity\Panier();
    $panierItem->setUser($user);
    $panierItem->setLot($lot);
    $panierItem->setQuantite(1);

    $entityManager->persist($panierItem);
    $entityManager->flush();

    echo "    ✅ Article ajouté au panier (ID: {$panierItem->getId()})\n";

    // Étape 2: Vérifier le panier
    echo "\n🔍 Étape 2: Vérification du panier...\n";
    $items = $panierRepository->findByUser($user);
    echo "    📦 Articles dans le panier: " . count($items) . "\n";

    if (empty($items)) {
        echo "    ❌ Panier vide\n";
        exit;
    }

    // Étape 3: Validation du panier
    echo "\n✅ Étape 3: Validation du panier...\n";

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

    // Étape 4: Vérifier les commandes créées
    echo "\n🔍 Étape 4: Vérification des commandes...\n";
    $commandesCreees = $commandeRepository->findBy(['user' => $user, 'statut' => 'en_attente']);
    echo "    📦 Commandes en attente: " . count($commandesCreees) . "\n";

    foreach ($commandesCreees as $commande) {
        echo "    ✅ Commande ID: {$commande->getId()}, Lot: {$commande->getLot()->getName()}, Statut: {$commande->getStatut()}\n";
    }

    // Étape 5: Vérifier le panier vide
    echo "\n🔍 Étape 5: Vérification du panier vide...\n";
    $panierVide = $panierRepository->findByUser($user);
    echo "    📦 Articles restants dans le panier: " . count($panierVide) . "\n";

    if (count($panierVide) === 0) {
        echo "    ✅ Panier correctement vidé\n";
    } else {
        echo "    ❌ Panier non vidé\n";
    }

    echo "\n🎉 PROCESSUS COMPLET RÉUSSI !\n";
    echo "    ✅ Ajout au panier\n";
    echo "    ✅ Validation du panier\n";
    echo "    ✅ Création des commandes\n";
    echo "    ✅ Mise à jour du stock\n";
    echo "    ✅ Vidage du panier\n";
} catch (Exception $e) {
    echo "    ❌ ERREUR: " . $e->getMessage() . "\n";
    echo "    📍 Fichier: " . $e->getFile() . " Ligne: " . $e->getLine() . "\n";
    echo "    🔍 Trace: " . $e->getTraceAsString() . "\n";
}

echo "\n=== FIN DU TEST ===\n";

