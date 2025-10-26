<?php

require_once 'vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;

// Charger les variables d'environnement
$dotenv = new Dotenv();
$dotenv->load('.env');

// Configuration de la base de données
$host = $_ENV['DATABASE_HOST'] ?? 'localhost';
$port = $_ENV['DATABASE_PORT'] ?? '3306';
$dbname = $_ENV['DATABASE_NAME'] ?? '3tek';
$username = $_ENV['DATABASE_USER'] ?? 'root';
$password = $_ENV['DATABASE_PASSWORD'] ?? '';

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "=== TEST LIBÉRATION AUTOMATIQUE ===\n\n";

    // ÉTAPE 1 : Remettre le lot en état "réservé" pour le test
    echo "🔧 ÉTAPE 1 : REMISE EN ÉTAT POUR LE TEST\n";
    echo "----------------------------------------\n";

    $stmt = $pdo->prepare("UPDATE lot SET statut = 'reserve', reserve_par_id = 3, reserve_at = NOW() WHERE id = 5");
    $stmt->execute();
    echo "✅ Lot HP Serveur remis en état 'réservé' pour l'utilisateur ID 3\n";

    // ÉTAPE 2 : Créer une file d'attente pour l'utilisateur ID 4
    echo "\n📋 ÉTAPE 2 : CRÉATION D'UNE FILE D'ATTENTE\n";
    echo "-------------------------------------------\n";

    // Supprimer d'abord toute file d'attente existante
    $stmt = $pdo->prepare("DELETE FROM file_attente WHERE lot_id = 5");
    $stmt->execute();

    // Créer une nouvelle file d'attente pour l'utilisateur ID 4
    $stmt = $pdo->prepare("INSERT INTO file_attente (lot_id, user_id, position, statut, created_at) VALUES (5, 4, 1, 'en_attente', NOW())");
    $stmt->execute();
    echo "✅ File d'attente créée pour l'utilisateur ID 4\n";

    // ÉTAPE 3 : Vérifier l'état avant suppression
    echo "\n🔍 ÉTAPE 3 : ÉTAT AVANT SUPPRESSION\n";
    echo "-----------------------------------\n";

    $stmt = $pdo->prepare("SELECT id, name, statut, reserve_par_id FROM lot WHERE id = 5");
    $stmt->execute();
    $lot = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "État du lot :\n";
    echo " - ID: {$lot['id']}\n";
    echo " - Nom: {$lot['name']}\n";
    echo " - Statut: {$lot['statut']}\n";
    echo " - Réservé par: {$lot['reserve_par_id']}\n";

    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM file_attente WHERE lot_id = 5");
    $stmt->execute();
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo " - Files d'attente: $count\n";

    echo "\n🎯 ÉTAPE 4 : INSTRUCTIONS POUR LE TEST\n";
    echo "------------------------------------\n";
    echo "Maintenant, allez dans l'interface admin :\n";
    echo "1. Ouvrez http://localhost:8080/admin\n";
    echo "2. Allez dans 'Files d'attente'\n";
    echo "3. Trouvez l'entrée pour l'utilisateur ID 4\n";
    echo "4. Supprimez cette entrée\n";
    echo "5. Le lot devrait automatiquement passer à 'disponible'\n";
    echo "6. Revenez ici pour vérifier le résultat\n\n";

    echo "Appuyez sur Entrée quand vous avez terminé le test...";
    fgets(STDIN);

    // ÉTAPE 5 : Vérifier l'état après suppression
    echo "\n🔍 ÉTAPE 5 : ÉTAT APRÈS SUPPRESSION\n";
    echo "-----------------------------------\n";

    $stmt = $pdo->prepare("SELECT id, name, statut, reserve_par_id FROM lot WHERE id = 5");
    $stmt->execute();
    $lot = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "État du lot :\n";
    echo " - ID: {$lot['id']}\n";
    echo " - Nom: {$lot['name']}\n";
    echo " - Statut: {$lot['statut']}\n";
    echo " - Réservé par: " . ($lot['reserve_par_id'] ?: 'NULL') . "\n";

    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM file_attente WHERE lot_id = 5");
    $stmt->execute();
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo " - Files d'attente: $count\n";

    if ($lot['statut'] === 'disponible' && $count == 0) {
        echo "\n✅ SUCCÈS ! La libération automatique fonctionne !\n";
    } else {
        echo "\n❌ PROBLÈME ! La libération automatique ne fonctionne pas.\n";
        echo "   - Statut attendu: 'disponible', obtenu: '{$lot['statut']}'\n";
        echo "   - Files d'attente attendues: 0, obtenues: $count\n";
    }

    echo "\n=== FIN DU TEST ===\n";
} catch (PDOException $e) {
    echo "❌ Erreur de base de données : " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "❌ Erreur : " . $e->getMessage() . "\n";
}

