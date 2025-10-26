<?php
// Script de vérification finale des corrections
require_once 'vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;

$dotenv = new Dotenv();
$dotenv->load('.env');

// Configuration Docker
$host = 'database';
$port = '3306';
$dbname = 'db_3tek';
$username = 'root';
$password = 'ngamba123';

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "=== VÉRIFICATION FINALE DES CORRECTIONS ===\n\n";

    // 1. Vérifier l'état des lots
    echo "1. ÉTAT DES LOTS:\n";
    echo "=================\n";

    $stmt = $pdo->query("SELECT id, name, statut, quantite, reserve_par_id FROM lot ORDER BY id");
    $lots = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $lotsReserves = 0;
    $lotsDisponibles = 0;

    foreach ($lots as $lot) {
        $status = $lot['statut'] === 'disponible' ? '✅ DISPONIBLE' : '⚠️ RÉSERVÉ';
        echo "{$status} | ID: {$lot['id']} | Nom: {$lot['name']} | Quantité: {$lot['quantite']}\n";

        if ($lot['statut'] === 'disponible') {
            $lotsDisponibles++;
        } else {
            $lotsReserves++;
        }
    }

    echo "\nRésumé: {$lotsDisponibles} lots disponibles, {$lotsReserves} lots réservés\n";

    // 2. Vérifier les commandes
    echo "\n2. ÉTAT DES COMMANDES:\n";
    echo "======================\n";

    $stmt = $pdo->query("SELECT COUNT(*) as total FROM commande");
    $totalCommandes = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    echo "Total des commandes: {$totalCommandes}\n";

    if ($totalCommandes > 0) {
        $stmt = $pdo->query("SELECT id, statut, lot_id, user_id, created_at FROM commande ORDER BY id DESC LIMIT 5");
        $commandes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($commandes as $commande) {
            echo "- Commande ID: {$commande['id']} | Statut: {$commande['statut']} | Lot ID: {$commande['lot_id']}\n";
        }
    } else {
        echo "✅ Aucune commande en cours - état propre\n";
    }

    // 3. Vérifier les files d'attente
    echo "\n3. ÉTAT DES FILES D'ATTENTE:\n";
    echo "===========================\n";

    $stmt = $pdo->query("SELECT COUNT(*) as total FROM file_attente");
    $totalFiles = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    echo "Total des files d'attente: {$totalFiles}\n";

    if ($totalFiles > 0) {
        $stmt = $pdo->query("SELECT id, lot_id, user_id, position, statut FROM file_attente ORDER BY lot_id, position");
        $files = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($files as $file) {
            echo "- File ID: {$file['id']} | Lot ID: {$file['lot_id']} | Position: {$file['position']} | Statut: {$file['statut']}\n";
        }
    } else {
        echo "✅ Aucune file d'attente active\n";
    }

    // 4. Vérifier les descriptions HTML
    echo "\n4. DESCRIPTIONS HTML:\n";
    echo "=====================\n";

    $stmt = $pdo->query("SELECT id, name, description FROM lot WHERE description LIKE '%<p>%' OR description LIKE '%<br>%'");
    $lotsHtml = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($lotsHtml as $lot) {
        echo "- ID: {$lot['id']} | Nom: {$lot['name']} | Description: {$lot['description']}\n";
    }

    // 5. Recommandations finales
    echo "\n5. RECOMMANDATIONS:\n";
    echo "===================\n";

    if ($lotsReserves === 0) {
        echo "✅ Tous les lots sont disponibles - état optimal\n";
    } else {
        echo "⚠️  {$lotsReserves} lot(s) encore réservé(s) - vérification nécessaire\n";
    }

    if ($totalCommandes === 0) {
        echo "✅ Aucune commande en cours - système propre\n";
    } else {
        echo "ℹ️  {$totalCommandes} commande(s) en cours\n";
    }

    if ($totalFiles === 0) {
        echo "✅ Aucune file d'attente - système propre\n";
    } else {
        echo "ℹ️  {$totalFiles} file(s) d'attente active(s)\n";
    }

    echo "\n=== VÉRIFICATION TERMINÉE ===\n";
    echo "L'application devrait maintenant fonctionner correctement.\n";
    echo "Testez l'interface utilisateur pour confirmer.\n";
} catch (Exception $e) {
    echo "Erreur: " . $e->getMessage() . "\n";
}



