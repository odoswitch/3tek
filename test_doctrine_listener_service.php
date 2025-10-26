<?php

require_once 'vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

// Charger les variables d'environnement
$dotenv = new Dotenv();
$dotenv->load('.env');

echo "=== TEST DOCTRINE LISTENER VIA SERVICE SYMFONY ===\n\n";

// Configuration de la base de données
$host = $_ENV['DATABASE_HOST'] ?? 'localhost';
$port = $_ENV['DATABASE_PORT'] ?? '3306';
$dbname = $_ENV['DATABASE_NAME'] ?? '3tek';
$username = $_ENV['DATABASE_USER'] ?? 'root';
$password = $_ENV['DATABASE_PASSWORD'] ?? '';

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "🔧 PRÉPARATION DU TEST\n";
    echo "======================\n";

    // Nettoyer et préparer
    $pdo->exec("DELETE FROM commande WHERE numero_commande LIKE 'TEST-%'");
    $pdo->exec("DELETE FROM file_attente WHERE lot_id IN (5, 13)");
    $pdo->exec("UPDATE lot SET statut = 'disponible', reserve_par_id = NULL, reserve_at = NULL WHERE id IN (5, 13)");

    // Créer des files d'attente
    $pdo->exec("INSERT INTO file_attente (lot_id, user_id, position, statut, created_at) VALUES (5, 3, 1, 'en_attente', NOW())");
    $pdo->exec("INSERT INTO file_attente (lot_id, user_id, position, statut, created_at) VALUES (5, 4, 2, 'en_attente', NOW())");

    // Créer une commande
    $pdo->exec("INSERT INTO commande (numero_commande, user_id, lot_id, quantite, prix_unitaire, prix_total, statut, created_at) VALUES ('TEST-SERVICE', 2, 5, 1, 12.00, 12.00, 'en_attente', NOW())");

    echo "✅ Environnement préparé\n\n";

    echo "🧪 TEST VIA SERVICE SYMFONY\n";
    echo "==========================\n";

    // Utiliser le service Symfony pour supprimer la commande
    $application = new Application();
    $application->setAutoExit(false);

    // Commande pour supprimer via Doctrine (qui déclenchera les listeners)
    $input = new ArrayInput([
        'command' => 'doctrine:query:sql',
        'sql' => "DELETE FROM commande WHERE numero_commande = 'TEST-SERVICE'"
    ]);

    $output = new BufferedOutput();
    $application->run($input, $output);

    echo "✅ Commande supprimée via service Symfony\n";

    // Vérifier le résultat
    $stmt = $pdo->query("SELECT id, name, statut, reserve_par_id FROM lot WHERE id = 5");
    $lot = $stmt->fetch(PDO::FETCH_ASSOC);

    echo "\n🔍 RÉSULTAT\n";
    echo "===========\n";
    echo "Lot {$lot['id']} ({$lot['name']}) : {$lot['statut']} (réservé par: " . ($lot['reserve_par_id'] ?: 'NULL') . ")\n";

    if ($lot['statut'] === 'reserve' && $lot['reserve_par_id'] == 3) {
        echo "✅ SUCCÈS : Le Doctrine Listener a fonctionné !\n";
    } else {
        echo "❌ ÉCHEC : Le Doctrine Listener n'a pas fonctionné\n";
    }

    echo "\n=== FIN DU TEST ===\n";
} catch (PDOException $e) {
    echo "❌ Erreur de base de données : " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "❌ Erreur : " . $e->getMessage() . "\n";
}

