<?php
// Script pour exécuter les corrections de la table file_attente
echo "=== EXÉCUTION DES CORRECTIONS ===\n\n";

try {
    // Connexion à la base de données
    $pdo = new PDO('mysql:host=database;dbname=db_3tek', 'root', 'ngamba123');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "✅ Connexion à la base de données réussie\n\n";

    // Vérifier la structure actuelle
    echo "🔍 Vérification de la structure actuelle...\n";
    $stmt = $pdo->query("DESCRIBE file_attente");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Colonnes actuelles:\n";
    foreach ($columns as $column) {
        echo "- {$column['Field']} ({$column['Type']})\n";
    }
    echo "\n";

    // Ajouter les colonnes manquantes
    $alterQueries = [
        "ALTER TABLE file_attente ADD COLUMN IF NOT EXISTS lot_id INT NULL",
        "ALTER TABLE file_attente ADD COLUMN IF NOT EXISTS user_id INT NULL",
        "ALTER TABLE file_attente ADD COLUMN IF NOT EXISTS position INT NULL",
        "ALTER TABLE file_attente ADD COLUMN IF NOT EXISTS created_at DATETIME NULL",
        "ALTER TABLE file_attente ADD COLUMN IF NOT EXISTS statut VARCHAR(50) DEFAULT 'en_attente'",
        "ALTER TABLE file_attente ADD COLUMN IF NOT EXISTS notified_at DATETIME NULL"
    ];

    echo "🔧 Ajout des colonnes manquantes...\n";
    foreach ($alterQueries as $query) {
        try {
            $pdo->exec($query);
            echo "✅ Requête exécutée: " . substr($query, 0, 50) . "...\n";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
                echo "ℹ️ Colonne déjà existante: " . substr($query, 0, 50) . "...\n";
            } else {
                echo "❌ Erreur: " . $e->getMessage() . "\n";
            }
        }
    }

    echo "\n🔗 Ajout des contraintes de clé étrangère...\n";

    // Ajouter les contraintes de clé étrangère
    $foreignKeyQueries = [
        "ALTER TABLE file_attente ADD CONSTRAINT FK_file_attente_lot FOREIGN KEY (lot_id) REFERENCES lot(id)",
        "ALTER TABLE file_attente ADD CONSTRAINT FK_file_attente_user FOREIGN KEY (user_id) REFERENCES user(id)"
    ];

    foreach ($foreignKeyQueries as $query) {
        try {
            $pdo->exec($query);
            echo "✅ Contrainte ajoutée: " . substr($query, 0, 50) . "...\n";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
                echo "ℹ️ Contrainte déjà existante: " . substr($query, 0, 50) . "...\n";
            } else {
                echo "❌ Erreur: " . $e->getMessage() . "\n";
            }
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
    echo "La table file_attente est maintenant compatible avec l'entité FileAttente.\n";
} catch (PDOException $e) {
    echo "❌ ERREUR DE CONNEXION: " . $e->getMessage() . "\n";
    echo "Vérifiez que le conteneur MySQL est démarré.\n";
} catch (Exception $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
}

echo "\n=== FIN DU SCRIPT ===\n";
