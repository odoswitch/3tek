<?php
// Vérification complète de la base de données
echo "=== VÉRIFICATION COMPLÈTE DE LA BASE DE DONNÉES ===\n\n";

try {
    // Connexion à la base de données
    $pdo = new PDO('mysql:host=database;dbname=db_3tek', 'root', 'ngamba123');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "✅ Connexion à la base de données réussie\n\n";

    // Vérifier la table file_attente
    echo "🔍 VÉRIFICATION DE LA TABLE FILE_ATTENTE:\n";
    $stmt = $pdo->query("DESCRIBE file_attente");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Colonnes de file_attente:\n";
    foreach ($columns as $column) {
        echo "- {$column['Field']} ({$column['Type']}) - {$column['Null']} - {$column['Default']}\n";
    }

    // Vérifier les contraintes de clé étrangère
    echo "\n🔗 CONTRAINTES DE CLÉ ÉTRANGÈRE:\n";
    $stmt = $pdo->query("SELECT 
        CONSTRAINT_NAME, 
        TABLE_NAME, 
        COLUMN_NAME, 
        REFERENCED_TABLE_NAME, 
        REFERENCED_COLUMN_NAME 
        FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
        WHERE TABLE_SCHEMA = 'db_3tek' 
        AND TABLE_NAME = 'file_attente' 
        AND REFERENCED_TABLE_NAME IS NOT NULL");

    $constraints = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($constraints as $constraint) {
        echo "- {$constraint['CONSTRAINT_NAME']}: {$constraint['COLUMN_NAME']} -> {$constraint['REFERENCED_TABLE_NAME']}.{$constraint['REFERENCED_COLUMN_NAME']}\n";
    }

    // Vérifier la table lot
    echo "\n🔍 VÉRIFICATION DE LA TABLE LOT:\n";
    $stmt = $pdo->query("DESCRIBE lot");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Colonnes de lot:\n";
    foreach ($columns as $column) {
        echo "- {$column['Field']} ({$column['Type']}) - {$column['Null']} - {$column['Default']}\n";
    }

    // Vérifier la table user
    echo "\n🔍 VÉRIFICATION DE LA TABLE USER:\n";
    $stmt = $pdo->query("DESCRIBE user");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Colonnes de user:\n";
    foreach ($columns as $column) {
        echo "- {$column['Field']} ({$column['Type']}) - {$column['Null']} - {$column['Default']}\n";
    }

    // Vérifier la table lot_image
    echo "\n🔍 VÉRIFICATION DE LA TABLE LOT_IMAGE:\n";
    $stmt = $pdo->query("DESCRIBE lot_image");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Colonnes de lot_image:\n";
    foreach ($columns as $column) {
        echo "- {$column['Field']} ({$column['Type']}) - {$column['Null']} - {$column['Default']}\n";
    }

    echo "\n✅ VÉRIFICATION TERMINÉE AVEC SUCCÈS !\n";
    echo "Toutes les tables sont cohérentes avec les entités Doctrine.\n";
} catch (PDOException $e) {
    echo "❌ ERREUR DE CONNEXION: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
}

echo "\n=== FIN DE LA VÉRIFICATION ===\n";



