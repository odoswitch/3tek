<?php
// Script de diagnostic complet pour vérifier l'état des lots et commandes
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

    echo "=== DIAGNOSTIC COMPLET - ÉTAT DES LOTS ET COMMANDES ===\n\n";

    // 1. Vérifier tous les lots
    echo "1. ÉTAT DE TOUS LES LOTS:\n";
    echo "========================\n";
    $stmt = $pdo->query("SELECT id, name, statut, quantite, reserve_par_id, reserve_at FROM lot ORDER BY id");
    $lots = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($lots as $lot) {
        echo "Lot ID: {$lot['id']} | Nom: {$lot['name']} | Statut: {$lot['statut']} | Quantité: {$lot['quantite']} | Réservé par: {$lot['reserve_par_id']} | Réservé le: {$lot['reserve_at']}\n";
    }

    echo "\n2. ÉTAT DE TOUTES LES COMMANDES:\n";
    echo "================================\n";
    $stmt = $pdo->query("SELECT id, statut, lot_id, user_id, created_at FROM commande ORDER BY id DESC LIMIT 10");
    $commandes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($commandes as $commande) {
        echo "Commande ID: {$commande['id']} | Statut: {$commande['statut']} | Lot ID: {$commande['lot_id']} | User ID: {$commande['user_id']} | Créée le: {$commande['created_at']}\n";
    }

    echo "\n3. ÉTAT DES FILES D'ATTENTE:\n";
    echo "===========================\n";
    $stmt = $pdo->query("SELECT id, lot_id, user_id, position, statut, created_at FROM file_attente ORDER BY lot_id, position");
    $filesAttente = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($filesAttente as $file) {
        echo "File ID: {$file['id']} | Lot ID: {$file['lot_id']} | User ID: {$file['user_id']} | Position: {$file['position']} | Statut: {$file['statut']} | Créée le: {$file['created_at']}\n";
    }

    echo "\n4. VÉRIFICATION SPÉCIFIQUE DU LOT 'SERVEURS':\n";
    echo "============================================\n";
    $stmt = $pdo->prepare("SELECT l.*, u.email as reserve_par_email FROM lot l LEFT JOIN user u ON l.reserve_par_id = u.id WHERE l.name = 'SERVEURS'");
    $stmt->execute();
    $serveursLot = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($serveursLot) {
        echo "Lot SERVEURS trouvé:\n";
        echo "- ID: {$serveursLot['id']}\n";
        echo "- Statut: {$serveursLot['statut']}\n";
        echo "- Quantité: {$serveursLot['quantite']}\n";
        echo "- Réservé par: {$serveursLot['reserve_par_id']} ({$serveursLot['reserve_par_email']})\n";
        echo "- Réservé le: {$serveursLot['reserve_at']}\n";
        echo "- Description: {$serveursLot['description']}\n";

        // Vérifier les commandes pour ce lot
        $stmt = $pdo->prepare("SELECT id, statut, user_id, created_at FROM commande WHERE lot_id = ? ORDER BY id DESC");
        $stmt->execute([$serveursLot['id']]);
        $commandesServeurs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo "\nCommandes pour le lot SERVEURS:\n";
        foreach ($commandesServeurs as $cmd) {
            echo "- Commande ID: {$cmd['id']} | Statut: {$cmd['statut']} | User ID: {$cmd['user_id']} | Créée le: {$cmd['created_at']}\n";
        }

        // Vérifier les files d'attente pour ce lot
        $stmt = $pdo->prepare("SELECT fa.*, u.email as user_email FROM file_attente fa LEFT JOIN user u ON fa.user_id = u.id WHERE fa.lot_id = ? ORDER BY fa.position");
        $stmt->execute([$serveursLot['id']]);
        $filesServeurs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo "\nFiles d'attente pour le lot SERVEURS:\n";
        foreach ($filesServeurs as $file) {
            echo "- Position: {$file['position']} | User: {$file['user_email']} | Statut: {$file['statut']}\n";
        }
    } else {
        echo "Lot SERVEURS non trouvé!\n";
    }

    echo "\n5. RECOMMANDATIONS:\n";
    echo "===================\n";

    // Analyser les problèmes
    $problemes = [];

    if ($serveursLot && $serveursLot['statut'] === 'reserve') {
        $commandesActives = array_filter($commandesServeurs, function ($cmd) {
            return in_array($cmd['statut'], ['reserve', 'validee', 'en_cours']);
        });

        if (empty($commandesActives)) {
            $problemes[] = "Le lot SERVEURS est réservé mais aucune commande active trouvée - libération nécessaire";
        }
    }

    if ($serveursLot && strpos($serveursLot['description'], '<p>') !== false) {
        $problemes[] = "La description contient du HTML non rendu - correction nécessaire";
    }

    foreach ($problemes as $probleme) {
        echo "- ⚠️  $probleme\n";
    }

    if (empty($problemes)) {
        echo "- ✅ Aucun problème détecté\n";
    }
} catch (Exception $e) {
    echo "Erreur: " . $e->getMessage() . "\n";
}
