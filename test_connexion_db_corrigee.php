<?php

echo "=== TEST CONNEXION BASE DE DONNÉES CORRIGÉE ===\n\n";

// Test de connexion avec les bonnes informations
try {
    $pdo = new PDO('mysql:host=3tek-database-1;port=3306;dbname=db_3tek', 'root', 'ngamba123');
    echo "✅ Connexion réussie avec les bonnes informations\n";

    // Test d'une requête simple
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM user");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "📊 Nombre d'utilisateurs: " . $result['count'] . "\n";

    $stmt = $pdo->query("SELECT COUNT(*) as count FROM lot");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "📦 Nombre de lots: " . $result['count'] . "\n";

    $stmt = $pdo->query("SELECT COUNT(*) as count FROM commande");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "🛒 Nombre de commandes: " . $result['count'] . "\n";

    $pdo = null;
} catch (Exception $e) {
    echo "❌ Échec connexion: " . $e->getMessage() . "\n";
}

echo "\n=== FIN DU TEST ===\n";

