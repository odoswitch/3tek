<?php
// Script pour ajouter les colonnes manquantes
echo "=== AJOUT DES COLONNES MANQUANTES ===\n\n";

try {
    // Connexion à la base de données
    $pdo = new PDO('mysql:host=database;dbname=db_3tek', 'root', 'ngamba123');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "✅ Connexion à la base de données réussie\n\n";

    // Ajouter la colonne statut
    echo "🔧 Ajout de la colonne 'statut'...\n";
    try {
        $pdo->exec("ALTER TABLE file_attente ADD COLUMN statut VARCHAR(50) DEFAULT 'en_attente'");
        echo "✅ Colonne 'statut' ajoutée avec succès\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo "ℹ️ Colonne 'statut' déjà existante\n";
        } else {
            echo "❌ Erreur: " . $e->getMessage() . "\n";
        }
    }

    // Ajouter la colonne notified_at
    echo "\n🔧 Ajout de la colonne 'notified_at'...\n";
    try {
        $pdo->exec("ALTER TABLE file_attente ADD COLUMN notified_at DATETIME NULL");
        echo "✅ Colonne 'notified_at' ajoutée avec succès\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo "ℹ️ Colonne 'notified_at' déjà existante\n";
        } else {
            echo "❌ Erreur: " . $e->getMessage() . "\n";
        }
    }

    echo "\n🔍 Vérification de la structure finale...\n";
    $stmt = $pdo->query("DESCRIBE file_attente");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Colonnes finales:\n";
    foreach ($columns as $column) {
        echo "- {$column['Field']} ({$column['Type']}) - {$column['Null']} - {$column['Default']}\n";
    }

    echo "\n✅ CORRECTION TERMINÉE AVEC SUCCÈS !\n";
    echo "La table file_attente contient maintenant toutes les colonnes requises.\n";
} catch (PDOException $e) {
    echo "❌ ERREUR DE CONNEXION: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
}

echo "\n=== FIN DU SCRIPT ===\n";



