<?php

require_once 'vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;

// Charger les variables d'environnement
$dotenv = new Dotenv();
$dotenv->load('.env');

echo "=== TEST DOCTRINE LISTENER CORRIGÉ ===\n\n";

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
    $pdo->exec("DELETE FROM file_attente WHERE lot_id = 5");
    $pdo->exec("UPDATE lot SET statut = 'disponible', reserve_par_id = NULL, reserve_at = NULL WHERE id = 5");

    // Créer une file d'attente pour tester
    $pdo->exec("INSERT INTO file_attente (lot_id, user_id, position, statut, created_at) VALUES (5, 3, 1, 'en_attente', NOW())");

    // Créer une commande de test
    $pdo->exec("INSERT INTO commande (numero_commande, user_id, lot_id, quantite, prix_unitaire, prix_total, statut, created_at) VALUES ('TEST-LISTENER', 2, 5, 1, 12.00, 12.00, 'en_attente', NOW())");

    echo "✅ Environnement préparé\n\n";

    echo "🔍 ÉTAT AVANT SUPPRESSION\n";
    echo "=========================\n";

    // Vérifier l'état avant
    $stmt = $pdo->query("SELECT id, name, statut, reserve_par_id FROM lot WHERE id = 5");
    $lotAvant = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Lot {$lotAvant['id']} ({$lotAvant['name']}) : {$lotAvant['statut']} (réservé par: " . ($lotAvant['reserve_par_id'] ?: 'NULL') . ")\n";

    $stmt = $pdo->query("SELECT COUNT(*) as nb FROM file_attente WHERE lot_id = 5");
    $nbFilesAvant = $stmt->fetch(PDO::FETCH_ASSOC)['nb'];
    echo "Files d'attente : {$nbFilesAvant}\n";

    echo "\n🧪 SUPPRESSION DE LA COMMANDE\n";
    echo "=============================\n";

    // Supprimer la commande (cela devrait déclencher le Doctrine Listener)
    $pdo->exec("DELETE FROM commande WHERE numero_commande = 'TEST-LISTENER'");

    echo "✅ Commande supprimée\n\n";

    echo "🔍 ÉTAT APRÈS SUPPRESSION\n";
    echo "=========================\n";

    // Vérifier l'état après
    $stmt = $pdo->query("SELECT id, name, statut, reserve_par_id FROM lot WHERE id = 5");
    $lotApres = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Lot {$lotApres['id']} ({$lotApres['name']}) : {$lotApres['statut']} (réservé par: " . ($lotApres['reserve_par_id'] ?: 'NULL') . ")\n";

    $stmt = $pdo->query("SELECT COUNT(*) as nb FROM file_attente WHERE lot_id = 5");
    $nbFilesApres = $stmt->fetch(PDO::FETCH_ASSOC)['nb'];
    echo "Files d'attente : {$nbFilesApres}\n";

    // Vérifier si le listener a fonctionné
    if ($lotApres['statut'] === 'reserve' && $lotApres['reserve_par_id'] == 3) {
        echo "\n✅ SUCCÈS : Le Doctrine Listener a fonctionné !\n";
        echo "Le lot a été réservé pour le premier utilisateur en file d'attente.\n";
    } elseif ($lotApres['statut'] === 'disponible') {
        echo "\n⚠️  ATTENTION : Le lot est disponible (aucune file d'attente active)\n";
    } else {
        echo "\n❌ ÉCHEC : Le Doctrine Listener n'a pas fonctionné\n";
    }

    echo "\n=== FIN DU TEST ===\n";
} catch (PDOException $e) {
    echo "❌ Erreur de base de données : " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "❌ Erreur : " . $e->getMessage() . "\n";
}

