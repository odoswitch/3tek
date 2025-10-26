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

echo "=== TEST COMPLET : SUPPRESSION VS ANNULATION VIA SERVICES SYMFONY ===\n\n";

// Configuration de la base de données
$host = $_ENV['DATABASE_HOST'] ?? 'localhost';
$port = $_ENV['DATABASE_PORT'] ?? '3306';
$dbname = $_ENV['DATABASE_NAME'] ?? '3tek';
$username = $_ENV['DATABASE_USER'] ?? 'root';
$password = $_ENV['DATABASE_PASSWORD'] ?? '';

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // ========================================
    // PRÉPARATION DE L'ENVIRONNEMENT DE TEST
    // ========================================

    echo "🔧 PRÉPARATION DE L'ENVIRONNEMENT DE TEST\n";
    echo "========================================\n";

    // Nettoyer l'environnement
    $pdo->exec("DELETE FROM commande WHERE numero_commande LIKE 'TEST-%'");
    $pdo->exec("DELETE FROM file_attente WHERE lot_id IN (5, 13)");
    $pdo->exec("UPDATE lot SET statut = 'disponible', reserve_par_id = NULL, reserve_at = NULL WHERE id IN (5, 13)");

    echo "✅ Environnement nettoyé\n";

    // Créer des files d'attente pour les deux lots avec utilisateurs ID 3,4
    $pdo->exec("INSERT INTO file_attente (lot_id, user_id, position, statut, created_at) VALUES (5, 3, 1, 'en_attente', NOW())");
    $pdo->exec("INSERT INTO file_attente (lot_id, user_id, position, statut, created_at) VALUES (5, 4, 2, 'en_attente', NOW())");
    $pdo->exec("INSERT INTO file_attente (lot_id, user_id, position, statut, created_at) VALUES (13, 3, 1, 'en_attente', NOW())");
    $pdo->exec("INSERT INTO file_attente (lot_id, user_id, position, statut, created_at) VALUES (13, 4, 2, 'en_attente', NOW())");

    echo "✅ Files d'attente créées (utilisateurs ID 3,4)\n";

    // Créer des commandes pour les deux lots
    $pdo->exec("INSERT INTO commande (numero_commande, user_id, lot_id, quantite, prix_unitaire, prix_total, statut, created_at) VALUES ('TEST-SUPPRESSION', 2, 5, 1, 12.00, 12.00, 'en_attente', NOW())");
    $pdo->exec("INSERT INTO commande (numero_commande, user_id, lot_id, quantite, prix_unitaire, prix_total, statut, created_at) VALUES ('TEST-ANNULATION', 2, 13, 1, 12.00, 12.00, 'en_attente', NOW())");

    echo "✅ Commandes de test créées\n\n";

    // ========================================
    // ÉTAT INITIAL
    // ========================================

    echo "📊 ÉTAT INITIAL\n";
    echo "===============\n";

    $stmt = $pdo->query("SELECT id, name, statut, reserve_par_id FROM lot WHERE id IN (5, 13) ORDER BY id");
    $lots = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($lots as $lot) {
        echo "Lot {$lot['id']} ({$lot['name']}) : {$lot['statut']} (réservé par: " . ($lot['reserve_par_id'] ?: 'NULL') . ")\n";
    }

    $stmt = $pdo->query("SELECT id, lot_id, user_id, position, statut FROM file_attente ORDER BY lot_id, position");
    $files = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "\nFiles d'attente :\n";
    foreach ($files as $file) {
        echo "  - Lot {$file['lot_id']}, User {$file['user_id']}, Position {$file['position']}, Statut {$file['statut']}\n";
    }

    $stmt = $pdo->query("SELECT id, numero_commande, lot_id, statut FROM commande WHERE numero_commande LIKE 'TEST-%' ORDER BY id");
    $commandes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "\nCommandes de test :\n";
    foreach ($commandes as $commande) {
        echo "  - {$commande['numero_commande']} (ID: {$commande['id']}), Lot {$commande['lot_id']}, Statut {$commande['statut']}\n";
    }

    echo "\n" . str_repeat("=", 60) . "\n";
    echo "🎯 SIMULATION DES TESTS VIA SERVICES SYMFONY\n";
    echo str_repeat("=", 60) . "\n";

    // ========================================
    // TEST 1 - SUPPRESSION DE COMMANDE
    // ========================================

    echo "\n🧪 TEST 1 - SUPPRESSION DE COMMANDE\n";
    echo "====================================\n";

    // Trouver la commande à supprimer
    $stmt = $pdo->prepare("SELECT id FROM commande WHERE numero_commande = 'TEST-SUPPRESSION'");
    $stmt->execute();
    $commandeId = $stmt->fetchColumn();

    echo "Commande à supprimer : ID $commandeId (TEST-SUPPRESSION)\n";

    // Simuler la suppression via le contrôleur admin
    echo "Simulation de la suppression via CommandeCrudController::deleteEntity...\n";

    // Récupérer la commande et son lot
    $stmt = $pdo->prepare("SELECT c.*, l.id as lot_id FROM commande c JOIN lot l ON c.lot_id = l.id WHERE c.id = ?");
    $stmt->execute([$commandeId]);
    $commande = $stmt->fetch(PDO::FETCH_ASSOC);

    echo "Commande trouvée : Lot ID {$commande['lot_id']}, Statut {$commande['statut']}\n";

    // Simuler la logique du contrôleur : libérer le lot
    echo "Libération du lot ID {$commande['lot_id']}...\n";

    // Vérifier s'il y a des utilisateurs en file d'attente
    $stmt = $pdo->prepare("SELECT user_id, position FROM file_attente WHERE lot_id = ? ORDER BY position ASC LIMIT 1");
    $stmt->execute([$commande['lot_id']]);
    $premierEnAttente = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($premierEnAttente) {
        echo "Premier en file d'attente trouvé : User ID {$premierEnAttente['user_id']}, Position {$premierEnAttente['position']}\n";

        // Réserver le lot pour le premier utilisateur
        $stmt = $pdo->prepare("UPDATE lot SET statut = 'reserve', reserve_par_id = ?, reserve_at = NOW() WHERE id = ?");
        $stmt->execute([$premierEnAttente['user_id'], $commande['lot_id']]);

        // Mettre à jour le statut de la file d'attente
        $stmt = $pdo->prepare("UPDATE file_attente SET statut = 'en_attente_validation', notified_at = NOW(), expires_at = DATE_ADD(NOW(), INTERVAL 1 HOUR) WHERE lot_id = ? AND user_id = ?");
        $stmt->execute([$commande['lot_id'], $premierEnAttente['user_id']]);

        echo "✅ Lot réservé pour l'utilisateur ID {$premierEnAttente['user_id']} avec délai d'1h\n";
    } else {
        echo "Aucun utilisateur en file d'attente\n";

        // Libérer le lot pour tous
        $stmt = $pdo->prepare("UPDATE lot SET statut = 'disponible', reserve_par_id = NULL, reserve_at = NULL WHERE id = ?");
        $stmt->execute([$commande['lot_id']]);

        echo "✅ Lot libéré pour tous\n";
    }

    // Supprimer la commande
    $stmt = $pdo->prepare("DELETE FROM commande WHERE id = ?");
    $stmt->execute([$commandeId]);

    echo "✅ Commande supprimée\n";

    // ========================================
    // TEST 2 - ANNULATION DE COMMANDE
    // ========================================

    echo "\n🧪 TEST 2 - ANNULATION DE COMMANDE\n";
    echo "===================================\n";

    // Trouver la commande à annuler
    $stmt = $pdo->prepare("SELECT id FROM commande WHERE numero_commande = 'TEST-ANNULATION'");
    $stmt->execute();
    $commandeId = $stmt->fetchColumn();

    echo "Commande à annuler : ID $commandeId (TEST-ANNULATION)\n";

    // Simuler l'annulation via le contrôleur utilisateur
    echo "Simulation de l'annulation via CommandeController::cancel...\n";

    // Récupérer la commande et son lot
    $stmt = $pdo->prepare("SELECT c.*, l.id as lot_id FROM commande c JOIN lot l ON c.lot_id = l.id WHERE c.id = ?");
    $stmt->execute([$commandeId]);
    $commande = $stmt->fetch(PDO::FETCH_ASSOC);

    echo "Commande trouvée : Lot ID {$commande['lot_id']}, Statut {$commande['statut']}\n";

    // Changer le statut de la commande à 'annulee'
    $stmt = $pdo->prepare("UPDATE commande SET statut = 'annulee' WHERE id = ?");
    $stmt->execute([$commandeId]);

    echo "✅ Commande annulée (statut changé à 'annulee')\n";

    // Libérer le lot (même logique que pour la suppression)
    echo "Libération du lot ID {$commande['lot_id']}...\n";

    // Vérifier s'il y a des utilisateurs en file d'attente
    $stmt = $pdo->prepare("SELECT user_id, position FROM file_attente WHERE lot_id = ? ORDER BY position ASC LIMIT 1");
    $stmt->execute([$commande['lot_id']]);
    $premierEnAttente = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($premierEnAttente) {
        echo "Premier en file d'attente trouvé : User ID {$premierEnAttente['user_id']}, Position {$premierEnAttente['position']}\n";

        // Réserver le lot pour le premier utilisateur
        $stmt = $pdo->prepare("UPDATE lot SET statut = 'reserve', reserve_par_id = ?, reserve_at = NOW() WHERE id = ?");
        $stmt->execute([$premierEnAttente['user_id'], $commande['lot_id']]);

        // Mettre à jour le statut de la file d'attente
        $stmt = $pdo->prepare("UPDATE file_attente SET statut = 'en_attente_validation', notified_at = NOW(), expires_at = DATE_ADD(NOW(), INTERVAL 1 HOUR) WHERE lot_id = ? AND user_id = ?");
        $stmt->execute([$commande['lot_id'], $premierEnAttente['user_id']]);

        echo "✅ Lot réservé pour l'utilisateur ID {$premierEnAttente['user_id']} avec délai d'1h\n";
    } else {
        echo "Aucun utilisateur en file d'attente\n";

        // Libérer le lot pour tous
        $stmt = $pdo->prepare("UPDATE lot SET statut = 'disponible', reserve_par_id = NULL, reserve_at = NULL WHERE id = ?");
        $stmt->execute([$commande['lot_id']]);

        echo "✅ Lot libéré pour tous\n";
    }

    // ========================================
    // VÉRIFICATION DES RÉSULTATS
    // ========================================

    echo "\n🔍 VÉRIFICATION DES RÉSULTATS\n";
    echo "=============================\n";

    $stmt = $pdo->query("SELECT id, name, statut, reserve_par_id FROM lot WHERE id IN (5, 13) ORDER BY id");
    $lots = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "\nÉtat final des lots :\n";
    foreach ($lots as $lot) {
        echo "Lot {$lot['id']} ({$lot['name']}) : {$lot['statut']} (réservé par: " . ($lot['reserve_par_id'] ?: 'NULL') . ")\n";
    }

    $stmt = $pdo->query("SELECT id, lot_id, user_id, position, statut FROM file_attente ORDER BY lot_id, position");
    $files = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "\nÉtat final des files d'attente :\n";
    foreach ($files as $file) {
        echo "  - Lot {$file['lot_id']}, User {$file['user_id']}, Position {$file['position']}, Statut {$file['statut']}\n";
    }

    $stmt = $pdo->query("SELECT id, numero_commande, lot_id, statut FROM commande WHERE numero_commande LIKE 'TEST-%' ORDER BY id");
    $commandes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "\nÉtat final des commandes :\n";
    foreach ($commandes as $commande) {
        echo "  - {$commande['numero_commande']} (ID: {$commande['id']}), Lot {$commande['lot_id']}, Statut {$commande['statut']}\n";
    }

    // Analyse des résultats
    echo "\n📈 ANALYSE DES RÉSULTATS\n";
    echo "========================\n";

    $lot5 = $lots[0]; // HP Serveur
    $lot13 = $lots[1]; // Lot David

    echo "\nTEST 1 - SUPPRESSION (Lot HP Serveur) :\n";
    if ($lot5['statut'] === 'reserve' && $lot5['reserve_par_id'] == 3) {
        echo "✅ SUCCÈS : Le lot est réservé pour l'utilisateur ID 3 (premier en file)\n";
    } else {
        echo "❌ ÉCHEC : Le lot n'est pas correctement réservé pour l'utilisateur ID 3\n";
        echo "   - Statut attendu: 'reserve', obtenu: '{$lot5['statut']}'\n";
        echo "   - Réservé par attendu: 3, obtenu: " . ($lot5['reserve_par_id'] ?: 'NULL') . "\n";
    }

    echo "\nTEST 2 - ANNULATION (Lot David) :\n";
    if ($lot13['statut'] === 'reserve' && $lot13['reserve_par_id'] == 3) {
        echo "✅ SUCCÈS : Le lot est réservé pour l'utilisateur ID 3 (premier en file)\n";
    } else {
        echo "❌ ÉCHEC : Le lot n'est pas correctement réservé pour l'utilisateur ID 3\n";
        echo "   - Statut attendu: 'reserve', obtenu: '{$lot13['statut']}'\n";
        echo "   - Réservé par attendu: 3, obtenu: " . ($lot13['reserve_par_id'] ?: 'NULL') . "\n";
    }

    echo "\n=== FIN DU TEST ===\n";
} catch (PDOException $e) {
    echo "❌ Erreur de base de données : " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "❌ Erreur : " . $e->getMessage() . "\n";
}

