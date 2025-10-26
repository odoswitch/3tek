<?php

require_once 'vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;

// Charger les variables d'environnement
$dotenv = new Dotenv();
$dotenv->load('.env');

echo "=== CORRECTION AUTOMATIQUE DES LOTS BLOQUÉS ===\n\n";

// Configuration de la base de données
$host = $_ENV['DATABASE_HOST'] ?? 'localhost';
$port = $_ENV['DATABASE_PORT'] ?? '3306';
$dbname = $_ENV['DATABASE_NAME'] ?? '3tek';
$username = $_ENV['DATABASE_USER'] ?? 'root';
$password = $_ENV['DATABASE_PASSWORD'] ?? '';

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "🔍 DIAGNOSTIC DES LOTS BLOQUÉS\n";
    echo "==============================\n";

    // Trouver les lots réservés sans commande active
    $stmt = $pdo->query("
        SELECT l.id, l.name, l.statut, l.reserve_par_id, l.reserve_at
        FROM lot l 
        WHERE l.statut = 'reserve' 
        AND NOT EXISTS (
            SELECT 1 FROM commande c 
            WHERE c.lot_id = l.id 
            AND c.statut IN ('en_attente', 'reserve', 'validee')
        )
    ");

    $lotsBloques = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($lotsBloques)) {
        echo "✅ Aucun lot bloqué trouvé\n";
    } else {
        echo "❌ " . count($lotsBloques) . " lot(s) bloqué(s) trouvé(s) :\n";
        foreach ($lotsBloques as $lot) {
            echo "   - Lot {$lot['id']} ({$lot['name']}) réservé par utilisateur {$lot['reserve_par_id']}\n";
        }

        echo "\n🔧 CORRECTION AUTOMATIQUE\n";
        echo "========================\n";

        foreach ($lotsBloques as $lot) {
            echo "Traitement du lot {$lot['id']} ({$lot['name']})...\n";

            // Vérifier s'il y a des utilisateurs en file d'attente
            $stmt = $pdo->prepare("
                SELECT user_id, position 
                FROM file_attente 
                WHERE lot_id = ? 
                AND statut IN ('en_attente', 'en_attente_validation', 'notifie', 'delai_depasse')
                ORDER BY position ASC 
                LIMIT 1
            ");
            $stmt->execute([$lot['id']]);
            $premierEnAttente = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($premierEnAttente) {
                echo "  → Premier en file d'attente trouvé : User ID {$premierEnAttente['user_id']}\n";

                // Réserver le lot pour le premier utilisateur
                $stmt = $pdo->prepare("
                    UPDATE lot 
                    SET statut = 'reserve', 
                        reserve_par_id = ?, 
                        reserve_at = NOW() 
                    WHERE id = ?
                ");
                $stmt->execute([$premierEnAttente['user_id'], $lot['id']]);

                // Mettre à jour le statut de la file d'attente
                $stmt = $pdo->prepare("
                    UPDATE file_attente 
                    SET statut = 'en_attente_validation', 
                        notified_at = NOW(), 
                        expires_at = DATE_ADD(NOW(), INTERVAL 1 HOUR) 
                    WHERE lot_id = ? AND user_id = ?
                ");
                $stmt->execute([$lot['id'], $premierEnAttente['user_id']]);

                echo "  ✅ Lot réservé pour l'utilisateur ID {$premierEnAttente['user_id']} avec délai d'1h\n";
            } else {
                echo "  → Aucun utilisateur en file d'attente\n";

                // Libérer le lot pour tous
                $stmt = $pdo->prepare("
                    UPDATE lot 
                    SET statut = 'disponible', 
                        reserve_par_id = NULL, 
                        reserve_at = NULL 
                    WHERE id = ?
                ");
                $stmt->execute([$lot['id']]);

                echo "  ✅ Lot libéré pour tous\n";
            }
        }
    }

    echo "\n🔍 VÉRIFICATION FINALE\n";
    echo "======================\n";

    $stmt = $pdo->query("SELECT id, name, statut, reserve_par_id FROM lot ORDER BY id");
    $lots = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($lots as $lot) {
        $reservePar = $lot['reserve_par_id'] ? "réservé par {$lot['reserve_par_id']}" : "libre";
        echo "Lot {$lot['id']} ({$lot['name']}) : {$lot['statut']} ({$reservePar})\n";
    }

    echo "\n=== CORRECTION TERMINÉE ===\n";
} catch (PDOException $e) {
    echo "❌ Erreur de base de données : " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "❌ Erreur : " . $e->getMessage() . "\n";
}

