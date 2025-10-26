<?php
// Script pour libérer le lot SERVEURS et corriger les problèmes
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

    echo "=== CORRECTION DES PROBLÈMES IDENTIFIÉS ===\n\n";

    // 1. Libérer le lot SERVEURS qui est réservé sans commande
    echo "1. LIBÉRATION DU LOT SERVEURS:\n";
    echo "===============================\n";

    $stmt = $pdo->prepare("UPDATE lot SET statut = 'disponible', reserve_par_id = NULL, reserve_at = NULL WHERE id = 14 AND name = 'Serveurs'");
    $result = $stmt->execute();

    if ($result) {
        echo "✅ Lot SERVEURS libéré avec succès\n";

        // Vérifier le changement
        $stmt = $pdo->prepare("SELECT id, name, statut, reserve_par_id FROM lot WHERE id = 14");
        $stmt->execute();
        $lot = $stmt->fetch(PDO::FETCH_ASSOC);

        echo "   - ID: {$lot['id']}\n";
        echo "   - Nom: {$lot['name']}\n";
        echo "   - Statut: {$lot['statut']}\n";
        echo "   - Réservé par: " . ($lot['reserve_par_id'] ?: 'NULL') . "\n";
    } else {
        echo "❌ Erreur lors de la libération du lot\n";
    }

    // 2. Corriger le rendu HTML des descriptions
    echo "\n2. CORRECTION DU RENDU HTML:\n";
    echo "============================\n";

    // Lister tous les lots avec des descriptions HTML
    $stmt = $pdo->query("SELECT id, name, description FROM lot WHERE description LIKE '%<p>%' OR description LIKE '%<br>%' OR description LIKE '%<div>%'");
    $lotsWithHtml = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Lots avec HTML dans la description:\n";
    foreach ($lotsWithHtml as $lot) {
        echo "- ID: {$lot['id']} | Nom: {$lot['name']} | Description: {$lot['description']}\n";
    }

    // 3. Vérifier l'état final
    echo "\n3. VÉRIFICATION DE L'ÉTAT FINAL:\n";
    echo "===============================\n";

    $stmt = $pdo->query("SELECT id, name, statut, quantite, reserve_par_id FROM lot ORDER BY id");
    $lots = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($lots as $lot) {
        $status = $lot['statut'] === 'disponible' ? '✅' : '⚠️';
        echo "{$status} Lot ID: {$lot['id']} | Nom: {$lot['name']} | Statut: {$lot['statut']} | Quantité: {$lot['quantite']}\n";
    }

    // 4. Vérifier les templates pour le rendu HTML
    echo "\n4. VÉRIFICATION DES TEMPLATES:\n";
    echo "==============================\n";

    $templates = [
        'templates/lot/view.html.twig',
        'templates/dash1.html.twig',
        'templates/favori/index.html.twig',
        'templates/emails/new_lot_notification.html.twig'
    ];

    foreach ($templates as $template) {
        if (file_exists($template)) {
            $content = file_get_contents($template);
            if (strpos($content, '|raw') !== false) {
                echo "✅ $template - Filtre |raw présent\n";
            } else {
                echo "⚠️  $template - Filtre |raw manquant\n";
            }
        } else {
            echo "❌ $template - Fichier non trouvé\n";
        }
    }

    echo "\n=== CORRECTION TERMINÉE ===\n";
    echo "Le lot SERVEURS devrait maintenant être disponible.\n";
    echo "Vérifiez l'interface utilisateur pour confirmer.\n";
} catch (Exception $e) {
    echo "Erreur: " . $e->getMessage() . "\n";
}



