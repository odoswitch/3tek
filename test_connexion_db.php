<?php

echo "=== TEST CONNEXION BASE DE DONNÉES ===\n\n";

// Test de connexion directe
try {
    $pdo = new PDO('mysql:host=3tek-database-1;port=3306;dbname=3tek', 'root', 'root');
    echo "✅ Connexion réussie avec 3tek-database-1\n";
    $pdo = null;
} catch (Exception $e) {
    echo "❌ Échec connexion avec 3tek-database-1: " . $e->getMessage() . "\n";
}

try {
    $pdo = new PDO('mysql:host=db;port=3306;dbname=3tek', 'root', 'root');
    echo "✅ Connexion réussie avec db\n";
    $pdo = null;
} catch (Exception $e) {
    echo "❌ Échec connexion avec db: " . $e->getMessage() . "\n";
}

try {
    $pdo = new PDO('mysql:host=172.18.0.2;port=3306;dbname=3tek', 'root', 'root');
    echo "✅ Connexion réussie avec IP directe (172.18.0.2)\n";
    $pdo = null;
} catch (Exception $e) {
    echo "❌ Échec connexion avec IP directe: " . $e->getMessage() . "\n";
}

echo "\n=== FIN DU TEST ===\n";

