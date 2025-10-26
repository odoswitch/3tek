<?php
require '/var/www/html/vendor/autoload.php';

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Setup;
use App\Entity\Lot;

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

echo "=== CORRECTION DU PRIX DU LOT ===\n\n";

// Trouver le lot "David"
$lotRepository = $entityManager->getRepository(Lot::class);
$lot = $lotRepository->findOneBy(['nom' => 'Lot David']);

if ($lot) {
    echo "Lot trouvé: " . $lot->getNom() . "\n";
    echo "Prix actuel: " . $lot->getPrix() . " €\n";
    echo "Quantité: " . $lot->getQuantite() . "\n";
    echo "Statut: " . $lot->getStatut() . "\n\n";

    // Corriger le prix si nécessaire
    if ($lot->getPrix() == 0) {
        echo "❌ Le lot a un prix de 0 € - Correction en cours...\n";
        $lot->setPrix(100.00); // Prix d'exemple
        $entityManager->persist($lot);
        $entityManager->flush();
        echo "✅ Prix corrigé à 100,00 €\n";
    } else {
        echo "✅ Le prix est correct: " . $lot->getPrix() . " €\n";
    }
} else {
    echo "❌ Lot non trouvé!\n";
}

echo "\n=== FIN DE LA CORRECTION ===\n";
