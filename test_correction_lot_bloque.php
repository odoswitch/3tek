<?php

require_once 'vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;

// Charger les variables d'environnement
$dotenv = new Dotenv();
$dotenv->load('.env');

echo "=== TEST DIRECT DU SERVICE DE LIBÉRATION ===\n\n";

// Configuration de la base de données
$host = $_ENV['DATABASE_HOST'] ?? 'localhost';
$port = $_ENV['DATABASE_PORT'] ?? '3306';
$dbname = $_ENV['DATABASE_NAME'] ?? '3tek';
$username = $_ENV['DATABASE_USER'] ?? 'root';
$password = $_ENV['DATABASE_PASSWORD'] ?? '';

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "🔍 ÉTAT ACTUEL\n";
    echo "==============\n";
    
    // Vérifier l'état du lot HP Serveur
    $stmt = $pdo->query("SELECT id, name, statut, reserve_par_id FROM lot WHERE id = 5");
    $lot = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "Lot {$lot['id']} ({$lot['name']}) : {$lot['statut']} (réservé par: " . ($lot['reserve_par_id'] ?: 'NULL') . ")\n";
    
    // Vérifier les files d'attente
    $stmt = $pdo->query("SELECT COUNT(*) as nb FROM file_attente WHERE lot_id = 5");
    $nbFiles = $stmt->fetch(PDO::FETCH_ASSOC)['nb'];
    
    echo "Files d'attente pour ce lot : {$nbFiles}\n";
    
    // Vérifier les commandes
    $stmt = $pdo->query("SELECT COUNT(*) as nb FROM commande WHERE lot_id = 5");
    $nbCommandes = $stmt->fetch(PDO::FETCH_ASSOC)['nb'];
    
    echo "Commandes pour ce lot : {$nbCommandes}\n\n";
    
    if ($nbFiles == 0 && $nbCommandes == 0 && $lot['statut'] == 'reserve') {
        echo "🐛 BUG CONFIRMÉ : Lot réservé sans commande ni file d'attente !\n\n";
        
        echo "🔧 CORRECTION DIRECTE\n";
        echo "=====================\n";
        
        // Correction directe
        $pdo->exec("UPDATE lot SET statut = 'disponible', reserve_par_id = NULL, reserve_at = NULL WHERE id = 5");
        
        echo "✅ Lot HP Serveur libéré et rendu disponible !\n\n";
        
        // Vérifier le résultat
        $stmt = $pdo->query("SELECT id, name, statut, reserve_par_id FROM lot WHERE id = 5");
        $lotCorrige = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "🔍 RÉSULTAT APRÈS CORRECTION\n";
        echo "============================\n";
        echo "Lot {$lotCorrige['id']} ({$lotCorrige['name']}) : {$lotCorrige['statut']} (réservé par: " . ($lotCorrige['reserve_par_id'] ?: 'NULL') . ")\n";
        
    } else {
        echo "✅ État normal du lot\n";
    }
    
    echo "\n=== FIN DU TEST ===\n";
    
} catch (PDOException $e) {
    echo "❌ Erreur de base de données : " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "❌ Erreur : " . $e->getMessage() . "\n";
}

