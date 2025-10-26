<?php

require_once 'vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;

// Charger les variables d'environnement
$dotenv = new Dotenv();
$dotenv->load('.env');

echo "=== TEST COMPLET : SUPPRESSION VS ANNULATION DE COMMANDE ===\n\n";

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

    // Créer des files d'attente pour les deux lots
    $pdo->exec("INSERT INTO file_attente (lot_id, user_id, position, statut, created_at) VALUES (5, 3, 1, 'en_attente', NOW())");
    $pdo->exec("INSERT INTO file_attente (lot_id, user_id, position, statut, created_at) VALUES (5, 4, 2, 'en_attente', NOW())");
    $pdo->exec("INSERT INTO file_attente (lot_id, user_id, position, statut, created_at) VALUES (13, 2, 1, 'en_attente', NOW())");
    $pdo->exec("INSERT INTO file_attente (lot_id, user_id, position, statut, created_at) VALUES (13, 3, 2, 'en_attente', NOW())");

    echo "✅ Files d'attente créées\n";

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
    echo "🎯 INSTRUCTIONS POUR LES TESTS\n";
    echo str_repeat("=", 60) . "\n";
    echo "\n";
    echo "TEST 1 - SUPPRESSION DE COMMANDE :\n";
    echo "1. Allez dans l'admin : http://localhost:8080/admin\n";
    echo "2. Cliquez sur 'Commandes'\n";
    echo "3. Trouvez la commande 'TEST-SUPPRESSION' (Lot HP Serveur)\n";
    echo "4. Supprimez cette commande (bouton rouge)\n";
    echo "5. Vérifiez que le lot HP Serveur passe à l'utilisateur ID 3 (premier en file)\n";
    echo "\n";
    echo "TEST 2 - ANNULATION DE COMMANDE :\n";
    echo "1. Restez dans l'admin\n";
    echo "2. Trouvez la commande 'TEST-ANNULATION' (Lot David)\n";
    echo "3. Changez le statut de 'en_attente' à 'annulee'\n";
    echo "4. Sauvegardez\n";
    echo "5. Vérifiez que le lot David passe à l'utilisateur ID 2 (premier en file)\n";
    echo "\n";
    echo "Appuyez sur Entrée quand vous avez terminé les deux tests...";
    fgets(STDIN);

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
    if ($lot13['statut'] === 'reserve' && $lot13['reserve_par_id'] == 2) {
        echo "✅ SUCCÈS : Le lot est réservé pour l'utilisateur ID 2 (premier en file)\n";
    } else {
        echo "❌ ÉCHEC : Le lot n'est pas correctement réservé pour l'utilisateur ID 2\n";
        echo "   - Statut attendu: 'reserve', obtenu: '{$lot13['statut']}'\n";
        echo "   - Réservé par attendu: 2, obtenu: " . ($lot13['reserve_par_id'] ?: 'NULL') . "\n";
    }

    echo "\n=== FIN DU TEST ===\n";
} catch (PDOException $e) {
    echo "❌ Erreur de base de données : " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "❌ Erreur : " . $e->getMessage() . "\n";
}

