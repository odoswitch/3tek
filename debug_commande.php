<?php
require '/var/www/html/vendor/autoload.php';

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Setup;
use App\Entity\User;
use App\Entity\Lot;
use App\Entity\Commande;

$paths = ['/var/www/html/src/Entity'];
$isDevMode = true;

$dbParams = [
    'driver'   => 'pdo_mysql',
    'user'     => 'root',
    'password' => 'root',
    'dbname'   => 'db_3tek',
    'host'     => 'database',
];

$config = Setup::createAnnotationMetadataConfiguration($paths, $isDevMode, null, null, false);
$entityManager = EntityManager::create($dbParams, $config);

echo "=== DIAGNOSTIC COMMANDE ===\n\n";

// 1. Vérifier le lot "David"
$lotRepository = $entityManager->getRepository(Lot::class);
$lot = $lotRepository->findOneBy(['nom' => 'Lot David']);

if ($lot) {
    echo "LOT TROUVÉ:\n";
    echo "- ID: " . $lot->getId() . "\n";
    echo "- Nom: " . $lot->getNom() . "\n";
    echo "- Quantité: " . $lot->getQuantite() . "\n";
    echo "- Statut: " . $lot->getStatut() . "\n";
    echo "- Prix: " . $lot->getPrix() . "\n";
    echo "- Réservé par: " . ($lot->getReservePar() ? $lot->getReservePar()->getEmail() : 'Aucun') . "\n";
    echo "- Réservé le: " . ($lot->getReserveAt() ? $lot->getReserveAt()->format('Y-m-d H:i:s') : 'Jamais') . "\n\n";
} else {
    echo "LOT NON TROUVÉ!\n\n";
}

// 2. Vérifier les commandes récentes
$commandeRepository = $entityManager->getRepository(Commande::class);
$commandes = $commandeRepository->findBy([], ['createdAt' => 'DESC'], 5);

echo "COMMANDES RÉCENTES:\n";
foreach ($commandes as $commande) {
    echo "- ID: " . $commande->getId() . "\n";
    echo "- Client: " . $commande->getUser()->getEmail() . "\n";
    echo "- Lot: " . $commande->getLot()->getNom() . "\n";
    echo "- Quantité: " . $commande->getQuantite() . "\n";
    echo "- Statut: " . $commande->getStatut() . "\n";
    echo "- Prix total: " . $commande->getPrixTotal() . " €\n";
    echo "- Créé le: " . $commande->getCreatedAt()->format('Y-m-d H:i:s') . "\n";
    echo "---\n";
}

// 3. Vérifier la logique de réservation
echo "\n=== ANALYSE DU PROBLÈME ===\n";

if ($lot && $lot->getQuantite() > 0) {
    echo "❌ PROBLÈME: Le lot a encore de la quantité disponible\n";
    echo "   Quantité actuelle: " . $lot->getQuantite() . "\n";
    echo "   Statut: " . $lot->getStatut() . "\n";
} else {
    echo "✅ Le lot semble correctement réservé\n";
}

// 4. Vérifier les commandes en attente
$commandesEnAttente = $commandeRepository->findBy(['statut' => 'en_attente']);
echo "\nCOMMANDES EN ATTENTE: " . count($commandesEnAttente) . "\n";

foreach ($commandesEnAttente as $commande) {
    echo "- Commande #" . $commande->getId() . " - " . $commande->getUser()->getEmail() . " - " . $commande->getLot()->getNom() . "\n";
}

echo "\n=== FIN DU DIAGNOSTIC ===\n";
