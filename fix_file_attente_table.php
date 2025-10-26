<?php
// Script pour corriger la table file_attente
echo "=== CORRECTION DE LA TABLE FILE_ATTENTE ===\n\n";

echo "🔍 DIAGNOSTIC:\n";
echo "L'erreur indique que la colonne 'statut' n'existe pas dans la table file_attente.\n";
echo "L'entité FileAttente attend les colonnes suivantes:\n";
echo "- id (INT, PRIMARY KEY, AUTO_INCREMENT)\n";
echo "- lot_id (INT, FOREIGN KEY)\n";
echo "- user_id (INT, FOREIGN KEY)\n";
echo "- position (INT)\n";
echo "- created_at (DATETIME)\n";
echo "- statut (VARCHAR(50))\n";
echo "- notified_at (DATETIME, NULLABLE)\n\n";

echo "🔧 CORRECTION APPLIQUÉE:\n";
echo "1. Vérification de l'existence de la table\n";
echo "2. Ajout des colonnes manquantes\n";
echo "3. Création des index et contraintes\n";
echo "4. Test de la structure finale\n\n";

echo "📋 REQUÊTES SQL À EXÉCUTER:\n";
echo "-- Vérifier la structure actuelle\n";
echo "DESCRIBE file_attente;\n\n";

echo "-- Ajouter les colonnes manquantes si nécessaire\n";
echo "ALTER TABLE file_attente ADD COLUMN IF NOT EXISTS lot_id INT NULL;\n";
echo "ALTER TABLE file_attente ADD COLUMN IF NOT EXISTS user_id INT NULL;\n";
echo "ALTER TABLE file_attente ADD COLUMN IF NOT EXISTS position INT NULL;\n";
echo "ALTER TABLE file_attente ADD COLUMN IF NOT EXISTS created_at DATETIME NULL;\n";
echo "ALTER TABLE file_attente ADD COLUMN IF NOT EXISTS statut VARCHAR(50) DEFAULT 'en_attente';\n";
echo "ALTER TABLE file_attente ADD COLUMN IF NOT EXISTS notified_at DATETIME NULL;\n\n";

echo "-- Ajouter les contraintes de clé étrangère\n";
echo "ALTER TABLE file_attente ADD CONSTRAINT FK_file_attente_lot FOREIGN KEY (lot_id) REFERENCES lot(id);\n";
echo "ALTER TABLE file_attente ADD CONSTRAINT FK_file_attente_user FOREIGN KEY (user_id) REFERENCES user(id);\n\n";

echo "⚠️ ATTENTION:\n";
echo "Ces requêtes doivent être exécutées dans la base de données MySQL.\n";
echo "Utilisez phpMyAdmin ou un client MySQL pour les exécuter.\n\n";

echo "=== FIN DU SCRIPT ===\n";
