# 🔐 Configuration PhpMyAdmin pour 3tek

## 📋 Informations d'accès PhpMyAdmin

### **Accès via Docker (Développement)**
- **URL** : http://45.11.51.2:8087
- **Serveur** : `database` (nom du conteneur)
- **Utilisateur** : `root`
- **Mot de passe** : `ngamba123`
- **Base de données** : `3tek`

### **Accès via cPanel (Production)**
- **URL** : https://votre-domaine.com/phpmyadmin
- **Serveur** : `localhost`
- **Utilisateur** : `[Votre utilisateur BDD cPanel]`
- **Mot de passe** : `[Votre mot de passe BDD cPanel]`
- **Base de données** : `[Nom de votre BDD]`

## 🗄️ Structure de la base de données 3tek

### **Tables principales :**
```sql
-- Table des utilisateurs
CREATE TABLE user (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(180) UNIQUE NOT NULL,
    roles JSON NOT NULL,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(255),
    last_name VARCHAR(255),
    phone VARCHAR(20),
    address TEXT,
    city VARCHAR(100),
    postal_code VARCHAR(10),
    country VARCHAR(100),
    is_verified BOOLEAN DEFAULT FALSE,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL
);

-- Table des catégories
CREATE TABLE category (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL
);

-- Table des types
CREATE TABLE type (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL
);

-- Table des lots
CREATE TABLE lot (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    image VARCHAR(255),
    status ENUM('available', 'reserved', 'sold') DEFAULT 'available',
    category_id INT,
    type_id INT,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    FOREIGN KEY (category_id) REFERENCES category(id),
    FOREIGN KEY (type_id) REFERENCES type(id)
);

-- Table des commandes
CREATE TABLE commande (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    lot_id INT NOT NULL,
    quantity INT DEFAULT 1,
    total_price DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'confirmed', 'cancelled', 'completed') DEFAULT 'pending',
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    FOREIGN KEY (user_id) REFERENCES user(id),
    FOREIGN KEY (lot_id) REFERENCES lot(id)
);

-- Table des favoris
CREATE TABLE favori (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    lot_id INT NOT NULL,
    created_at DATETIME NOT NULL,
    FOREIGN KEY (user_id) REFERENCES user(id),
    FOREIGN KEY (lot_id) REFERENCES lot(id),
    UNIQUE KEY unique_user_lot (user_id, lot_id)
);

-- Table des logs email
CREATE TABLE email_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    recipient VARCHAR(255) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    body TEXT,
    sent_at DATETIME NOT NULL,
    status ENUM('sent', 'failed') DEFAULT 'sent'
);

-- Table des files d'attente
CREATE TABLE file_attente (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    lot_id INT NOT NULL,
    position INT NOT NULL,
    created_at DATETIME NOT NULL,
    FOREIGN KEY (user_id) REFERENCES user(id),
    FOREIGN KEY (lot_id) REFERENCES lot(id)
);
```

## 🔧 Configuration PhpMyAdmin

### **Fichier de configuration config.inc.php**
```php
<?php
/**
 * Configuration PhpMyAdmin pour 3tek
 */

$cfg['Servers'][$i]['host'] = 'localhost';
$cfg['Servers'][$i]['port'] = '3306';
$cfg['Servers'][$i]['socket'] = '';
$cfg['Servers'][$i]['ssl'] = false;
$cfg['Servers'][$i]['compress'] = false;
$cfg['Servers'][$i]['user'] = '';
$cfg['Servers'][$i]['password'] = '';
$cfg['Servers'][$i]['auth_type'] = 'cookie';
$cfg['Servers'][$i]['AllowNoPassword'] = false;

// Configuration de sécurité
$cfg['blowfish_secret'] = 'your-secret-key-here';
$cfg['ForceSSL'] = true;
$cfg['CheckConfigurationPermissions'] = true;

// Configuration de l'interface
$cfg['DefaultLang'] = 'fr';
$cfg['ServerDefault'] = 1;
$cfg['UploadDir'] = '';
$cfg['SaveDir'] = '';

// Limites
$cfg['MaxRows'] = 50;
$cfg['ExecTimeLimit'] = 300;
$cfg['MemoryLimit'] = '512M';

// Thème
$cfg['ThemeDefault'] = 'pmahomme';
?>
```

## 🚀 Scripts de gestion de base de données

### **Script de sauvegarde (backup-db.sh)**
```bash
#!/bin/bash

# Configuration
DB_NAME="3tek"
DB_USER="root"
DB_PASSWORD="ngamba123"
BACKUP_DIR="/home/backups"
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")

# Créer le répertoire de sauvegarde
mkdir -p "$BACKUP_DIR"

# Sauvegarde complète
mysqldump -u "$DB_USER" -p"$DB_PASSWORD" \
    --single-transaction \
    --routines \
    --triggers \
    --events \
    --add-drop-database \
    --databases "$DB_NAME" > "$BACKUP_DIR/3tek_full_backup_$TIMESTAMP.sql"

# Sauvegarde des données uniquement
mysqldump -u "$DB_USER" -p"$DB_PASSWORD" \
    --no-create-info \
    --no-create-db \
    --single-transaction \
    "$DB_NAME" > "$BACKUP_DIR/3tek_data_backup_$TIMESTAMP.sql"

echo "Sauvegarde créée: $BACKUP_DIR/3tek_full_backup_$TIMESTAMP.sql"
```

### **Script de restauration (restore-db.sh)**
```bash
#!/bin/bash

# Configuration
DB_NAME="3tek"
DB_USER="root"
DB_PASSWORD="ngamba123"
BACKUP_FILE="$1"

if [ -z "$BACKUP_FILE" ]; then
    echo "Usage: $0 <backup_file.sql>"
    exit 1
fi

if [ ! -f "$BACKUP_FILE" ]; then
    echo "Fichier de sauvegarde non trouvé: $BACKUP_FILE"
    exit 1
fi

# Restauration
mysql -u "$DB_USER" -p"$DB_PASSWORD" < "$BACKUP_FILE"

echo "Restauration terminée depuis: $BACKUP_FILE"
```

## 🔍 Requêtes utiles pour PhpMyAdmin

### **Vérification de l'état de la base**
```sql
-- Vérifier les tables
SHOW TABLES;

-- Vérifier la taille de la base
SELECT 
    table_schema AS 'Database',
    ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS 'Size (MB)'
FROM information_schema.tables 
WHERE table_schema = '3tek'
GROUP BY table_schema;

-- Vérifier les utilisateurs
SELECT COUNT(*) as total_users FROM user;

-- Vérifier les commandes
SELECT 
    status,
    COUNT(*) as count
FROM commande 
GROUP BY status;
```

### **Maintenance de la base**
```sql
-- Optimiser les tables
OPTIMIZE TABLE user, lot, commande, category, type;

-- Vérifier les tables
CHECK TABLE user, lot, commande, category, type;

-- Réparer les tables (si nécessaire)
REPAIR TABLE user, lot, commande, category, type;
```

## 🛡️ Sécurité PhpMyAdmin

### **Configuration .htaccess**
```apache
# Protection PhpMyAdmin
<Files "config.inc.php">
    Order allow,deny
    Deny from all
</Files>

# Limitation d'accès par IP (optionnel)
<RequireAll>
    Require ip 192.168.1.0/24
    Require ip 10.0.0.0/8
</RequireAll>

# Protection contre les attaques
RewriteEngine On
RewriteCond %{QUERY_STRING} (\<|%3C).*script.*(\>|%3E) [NC,OR]
RewriteCond %{QUERY_STRING} GLOBALS(=|\[|\%[0-9A-Z]{0,2}) [OR]
RewriteCond %{QUERY_STRING} _REQUEST(=|\[|\%[0-9A-Z]{0,2}) [OR]
RewriteCond %{QUERY_STRING} ^.*(\[|\]|\(|\)|<|>|ê|"|;|\?|\*|=$).* [NC,OR]
RewriteCond %{QUERY_STRING} (NULL|OUTFILE|LOAD_FILE) [OR]
RewriteCond %{QUERY_STRING} (\./|\../|\.../)+(motd|etc|bin) [NC,OR]
RewriteCond %{QUERY_STRING} (localhost|loopback|127\.0\.0\.1) [NC,OR]
RewriteCond %{QUERY_STRING} (<|>|'|%0A|%0D|%27|%3C|%3E|%00) [NC,OR]
RewriteCond %{QUERY_STRING} concat[^\(]*\( [NC,OR]
RewriteCond %{QUERY_STRING} union([^s]*s)+elect [NC,OR]
RewriteCond %{QUERY_STRING} union([^a]*a)+ll([^s]*s)+elect [NC,OR]
RewriteCond %{QUERY_STRING} (;|<|>|'|"|\)|%0A|%0D|%22|%27|%3C|%3E|%00).*(/\*|union|select|insert|drop|delete|update|cast|create|char|convert|alter|declare|order|script|set|md5|benchmark|encode) [NC,OR]
RewriteCond %{QUERY_STRING} (sp_executesql) [NC]
RewriteRule ^(.*)$ - [F,L]
```

## 📊 Monitoring de la base de données

### **Script de monitoring (monitor-db.sh)**
```bash
#!/bin/bash

# Configuration
DB_NAME="3tek"
DB_USER="root"
DB_PASSWORD="ngamba123"

echo "=== Monitoring Base de données 3tek ==="
echo "Date: $(date)"
echo ""

# Connexions actives
echo "Connexions actives:"
mysql -u "$DB_USER" -p"$DB_PASSWORD" -e "SHOW PROCESSLIST;" | grep -v "Sleep"

echo ""

# Taille de la base
echo "Taille de la base de données:"
mysql -u "$DB_USER" -p"$DB_PASSWORD" -e "
SELECT 
    table_schema AS 'Database',
    ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS 'Size (MB)'
FROM information_schema.tables 
WHERE table_schema = '$DB_NAME'
GROUP BY table_schema;"

echo ""

# Statistiques des tables
echo "Statistiques des tables:"
mysql -u "$DB_USER" -p"$DB_PASSWORD" -e "
SELECT 
    table_name AS 'Table',
    table_rows AS 'Rows',
    ROUND((data_length + index_length) / 1024 / 1024, 2) AS 'Size (MB)'
FROM information_schema.tables 
WHERE table_schema = '$DB_NAME'
ORDER BY (data_length + index_length) DESC;"
```

---

**Configuration PhpMyAdmin générée le : 28 octobre 2025**  
**Application : 3tek Symfony 7.3**  
**Base de données : MySQL 8.0**
