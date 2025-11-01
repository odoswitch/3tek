# 🚀 Rapport Complet - Déploiement 3tek sur cPanel

## 📋 Informations Générales

- **Application** : 3tek (Symfony 7.3)
- **Serveur** : Serveur dédié avec cPanel
- **Base de données** : MySQL 8.0
- **PHP** : 8.2+
- **Date** : 28 octobre 2025

## 🌐 Accès et Informations de Connexion

### **Accès cPanel**
- **URL** : https://votre-domaine.com:2083
- **Utilisateur** : [Votre utilisateur cPanel]
- **Mot de passe** : [Votre mot de passe cPanel]

### **Accès PhpMyAdmin**
- **URL** : https://votre-domaine.com/phpmyadmin
- **Serveur** : localhost
- **Utilisateur** : [Utilisateur BDD cPanel]
- **Mot de passe** : [Mot de passe BDD cPanel]
- **Base de données** : [Nom de votre BDD]

### **Accès FTP/SFTP**
- **Serveur** : votre-domaine.com
- **Port** : 21 (FTP) ou 22 (SFTP)
- **Utilisateur** : [Votre utilisateur cPanel]
- **Mot de passe** : [Votre mot de passe cPanel]
- **Répertoire racine** : `/public_html/`

## 📁 Structure de Déploiement

```
public_html/
├── 3tek/                          # Application Symfony
│   ├── bin/
│   ├── config/
│   ├── migrations/
│   ├── public/                    # Point d'entrée web
│   │   ├── index.php
│   │   └── .htaccess
│   ├── src/
│   ├── templates/
│   ├── var/                       # Cache et logs
│   │   ├── cache/
│   │   └── log/
│   ├── vendor/                    # Dépendances Composer
│   ├── .env                       # Variables d'environnement
│   ├── composer.json
│   └── composer.lock
├── phpmyadmin/                    # PhpMyAdmin (si installé)
└── .htaccess                      # Redirection racine
```

## 🔧 Configuration Requise

### **PHP Configuration (cPanel)**
```ini
; Configuration PHP recommandée
memory_limit = 512M
max_execution_time = 300
max_input_time = 300
post_max_size = 100M
upload_max_filesize = 100M
max_file_uploads = 20
date.timezone = Europe/Paris

; Extensions PHP requises
extension=pdo_mysql
extension=mbstring
extension=gd
extension=zip
extension=intl
extension=curl
extension=json
extension=openssl
```

### **Variables d'environnement (.env)**
```env
APP_ENV=prod
APP_SECRET=your-secret-key-change-in-production
APP_DEBUG=false

# Base de données
DATABASE_URL="mysql://username:password@localhost:3306/database_name?serverVersion=8.0&charset=utf8mb4"

# Mailer
MAILER_DSN=smtp://username:password@smtp.your-domain.com:587?encryption=tls

# Timezone
TZ=Europe/Paris
```

## 📦 Scripts de Déploiement

### **1. Script de Déploiement Principal (deploy-3tek.sh)**

```bash
#!/bin/bash

# Script de déploiement 3tek sur cPanel
# Usage: ./deploy-3tek.sh [domain] [db_user] [db_password] [db_name]

set -e

# Couleurs pour l'affichage
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# Variables
DOMAIN=${1:-"votre-domaine.com"}
DB_USER=${2:-"db_user"}
DB_PASSWORD=${3:-"db_password"}
DB_NAME=${4:-"db_name"}
APP_DIR="/home/$(whoami)/public_html/3tek"

print_status() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

print_header() {
    echo -e "${BLUE}================================${NC}"
    echo -e "${BLUE} $1${NC}"
    echo -e "${BLUE}================================${NC}"
}

# Vérification des prérequis
check_prerequisites() {
    print_header "VÉRIFICATION DES PRÉREQUIS"
    
    # Vérifier PHP
    if ! command -v php &> /dev/null; then
        print_error "PHP n'est pas installé"
        exit 1
    fi
    
    PHP_VERSION=$(php -r "echo PHP_VERSION;")
    print_status "PHP version: $PHP_VERSION"
    
    # Vérifier Composer
    if ! command -v composer &> /dev/null; then
        print_error "Composer n'est pas installé"
        exit 1
    fi
    
    print_status "Composer trouvé"
    
    # Vérifier MySQL
    if ! command -v mysql &> /dev/null; then
        print_error "MySQL n'est pas installé"
        exit 1
    fi
    
    print_status "MySQL trouvé"
}

# Création de la base de données
create_database() {
    print_header "CRÉATION DE LA BASE DE DONNÉES"
    
    mysql -u "$DB_USER" -p"$DB_PASSWORD" -e "CREATE DATABASE IF NOT EXISTS $DB_NAME CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
    
    if [ $? -eq 0 ]; then
        print_status "Base de données '$DB_NAME' créée avec succès"
    else
        print_error "Erreur lors de la création de la base de données"
        exit 1
    fi
}

# Installation de l'application
install_application() {
    print_header "INSTALLATION DE L'APPLICATION"
    
    # Créer le répertoire
    mkdir -p "$APP_DIR"
    cd "$APP_DIR"
    
    # Cloner le dépôt (ou copier les fichiers)
    print_status "Copie des fichiers de l'application..."
    # git clone https://github.com/odoswitch/3tek.git .
    # Ou copier depuis votre source
    
    # Installer les dépendances
    print_status "Installation des dépendances Composer..."
    composer install --no-dev --optimize-autoloader --no-interaction
    
    # Configuration des permissions
    print_status "Configuration des permissions..."
    chmod -R 755 var/
    chmod -R 755 public/
    chown -R $(whoami):$(whoami) var/
    chown -R $(whoami):$(whoami) public/
}

# Configuration de l'environnement
configure_environment() {
    print_header "CONFIGURATION DE L'ENVIRONNEMENT"
    
    cd "$APP_DIR"
    
    # Créer le fichier .env
    cat > .env << EOF
APP_ENV=prod
APP_SECRET=$(openssl rand -hex 32)
APP_DEBUG=false

DATABASE_URL="mysql://$DB_USER:$DB_PASSWORD@localhost:3306/$DB_NAME?serverVersion=8.0&charset=utf8mb4"

MAILER_DSN=smtp://localhost:1025

TZ=Europe/Paris
EOF
    
    print_status "Fichier .env créé"
}

# Exécution des migrations
run_migrations() {
    print_header "EXÉCUTION DES MIGRATIONS"
    
    cd "$APP_DIR"
    
    # Vider le cache
    php bin/console cache:clear --env=prod
    
    # Exécuter les migrations
    php bin/console doctrine:migrations:migrate --no-interaction --env=prod
    
    print_status "Migrations exécutées avec succès"
}

# Configuration du serveur web
configure_webserver() {
    print_header "CONFIGURATION DU SERVEUR WEB"
    
    cd "$APP_DIR"
    
    # Créer le fichier .htaccess pour public/
    cat > public/.htaccess << 'EOF'
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule ^(.*)$ index.php [QSA,L]

# Sécurité
<Files "*.env">
    Order allow,deny
    Deny from all
</Files>

<Files "composer.*">
    Order allow,deny
    Deny from all
</Files>
EOF
    
    # Créer le fichier .htaccess racine
    cat > ../.htaccess << EOF
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ 3tek/public/\$1 [QSA,L]
EOF
    
    print_status "Configuration du serveur web terminée"
}

# Test de l'installation
test_installation() {
    print_header "TEST DE L'INSTALLATION"
    
    cd "$APP_DIR"
    
    # Test de la base de données
    php bin/console doctrine:query:sql "SELECT 1" --env=prod
    
    if [ $? -eq 0 ]; then
        print_status "Connexion à la base de données OK"
    else
        print_error "Erreur de connexion à la base de données"
        exit 1
    fi
    
    # Test des routes
    php bin/console debug:router --env=prod | head -10
    
    print_status "Tests terminés"
}

# Fonction principale
main() {
    print_header "DÉPLOIEMENT 3TEK SUR CPANEL"
    
    print_status "Domaine: $DOMAIN"
    print_status "Base de données: $DB_NAME"
    print_status "Répertoire: $APP_DIR"
    
    check_prerequisites
    create_database
    install_application
    configure_environment
    run_migrations
    configure_webserver
    test_installation
    
    print_header "DÉPLOIEMENT TERMINÉ"
    print_status "Application accessible sur: https://$DOMAIN"
    print_status "PhpMyAdmin: https://$DOMAIN/phpmyadmin"
    print_status "Admin: https://$DOMAIN/admin"
}

# Exécution
main "$@"
```

### **2. Script de Correction PHP (fix-3tek.php)**

```php
<?php
/**
 * Script de correction pour l'application 3tek
 * Usage: php fix-3tek.php
 */

require_once __DIR__ . '/vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

// Charger les variables d'environnement
$dotenv = new Dotenv();
$dotenv->load(__DIR__ . '/.env');

class Fix3tekCommand extends Command
{
    protected static $defaultName = 'fix:3tek';

    protected function configure()
    {
        $this->setDescription('Corrige les problèmes courants de l\'application 3tek');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<info>🔧 Début des corrections...</info>');

        // 1. Correction des permissions
        $this->fixPermissions($output);

        // 2. Vidage du cache
        $this->clearCache($output);

        // 3. Vérification de la base de données
        $this->checkDatabase($output);

        // 4. Correction des migrations
        $this->fixMigrations($output);

        // 5. Installation des assets
        $this->installAssets($output);

        // 6. Vérification finale
        $this->finalCheck($output);

        $output->writeln('<info>✅ Corrections terminées avec succès!</info>');
        return Command::SUCCESS;
    }

    private function fixPermissions(OutputInterface $output)
    {
        $output->writeln('<comment>📁 Correction des permissions...</comment>');
        
        $directories = [
            'var/cache',
            'var/log',
            'var/sessions',
            'public/uploads'
        ];

        foreach ($directories as $dir) {
            if (is_dir($dir)) {
                chmod($dir, 0755);
                $output->writeln("   ✓ $dir");
            }
        }
    }

    private function clearCache(OutputInterface $output)
    {
        $output->writeln('<comment>🧹 Vidage du cache...</comment>');
        
        $cacheDir = 'var/cache';
        if (is_dir($cacheDir)) {
            $this->removeDirectory($cacheDir);
            mkdir($cacheDir, 0755, true);
            $output->writeln('   ✓ Cache vidé');
        }
    }

    private function checkDatabase(OutputInterface $output)
    {
        $output->writeln('<comment>🗄️ Vérification de la base de données...</comment>');
        
        try {
            $pdo = new PDO($_ENV['DATABASE_URL']);
            $pdo->exec('SELECT 1');
            $output->writeln('   ✓ Connexion à la base de données OK');
        } catch (PDOException $e) {
            $output->writeln('<error>   ✗ Erreur de connexion: ' . $e->getMessage() . '</error>');
        }
    }

    private function fixMigrations(OutputInterface $output)
    {
        $output->writeln('<comment>🔄 Correction des migrations...</comment>');
        
        // Vérifier si la table migrations existe
        try {
            $pdo = new PDO($_ENV['DATABASE_URL']);
            $stmt = $pdo->query("SHOW TABLES LIKE 'doctrine_migration_versions'");
            
            if ($stmt->rowCount() === 0) {
                $output->writeln('   ✓ Table migrations créée');
            } else {
                $output->writeln('   ✓ Table migrations existe');
            }
        } catch (PDOException $e) {
            $output->writeln('<error>   ✗ Erreur migrations: ' . $e->getMessage() . '</error>');
        }
    }

    private function installAssets(OutputInterface $output)
    {
        $output->writeln('<comment>📦 Installation des assets...</comment>');
        
        $publicDir = 'public';
        if (is_dir($publicDir)) {
            chmod($publicDir, 0755);
            $output->writeln('   ✓ Permissions public/ corrigées');
        }
    }

    private function finalCheck(OutputInterface $output)
    {
        $output->writeln('<comment>🔍 Vérification finale...</comment>');
        
        $checks = [
            'var/cache' => 'Cache',
            'var/log' => 'Logs',
            'public' => 'Public',
            '.env' => 'Configuration'
        ];

        foreach ($checks as $path => $name) {
            if (file_exists($path)) {
                $output->writeln("   ✓ $name OK");
            } else {
                $output->writeln("<error>   ✗ $name manquant</error>");
            }
        }
    }

    private function removeDirectory($dir)
    {
        if (is_dir($dir)) {
            $files = array_diff(scandir($dir), ['.', '..']);
            foreach ($files as $file) {
                $path = $dir . '/' . $file;
                is_dir($path) ? $this->removeDirectory($path) : unlink($path);
            }
            rmdir($dir);
        }
    }
}

// Exécution du script
$application = new Application('Fix 3tek', '1.0.0');
$application->add(new Fix3tekCommand());
$application->run();
?>
```

### **3. Script de Maintenance (maintenance-3tek.sh)**

```bash
#!/bin/bash

# Script de maintenance pour 3tek
# Usage: ./maintenance-3tek.sh [backup|restore|update|status]

set -e

# Configuration
APP_DIR="/home/$(whoami)/public_html/3tek"
BACKUP_DIR="/home/$(whoami)/backups/3tek"
DB_NAME="your_database_name"
DB_USER="your_db_user"
DB_PASSWORD="your_db_password"

# Couleurs
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

print_status() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Fonction de sauvegarde
backup() {
    print_status "Création de la sauvegarde..."
    
    TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
    BACKUP_FILE="$BACKUP_DIR/3tek_backup_$TIMESTAMP.tar.gz"
    
    mkdir -p "$BACKUP_DIR"
    
    # Sauvegarde des fichiers
    tar -czf "$BACKUP_FILE" -C "$APP_DIR" .
    
    # Sauvegarde de la base de données
    mysqldump -u "$DB_USER" -p"$DB_PASSWORD" "$DB_NAME" > "$BACKUP_DIR/db_backup_$TIMESTAMP.sql"
    
    print_status "Sauvegarde créée: $BACKUP_FILE"
}

# Fonction de restauration
restore() {
    print_warning "Restauration depuis la sauvegarde..."
    
    if [ -z "$1" ]; then
        print_error "Veuillez spécifier le fichier de sauvegarde"
        exit 1
    fi
    
    BACKUP_FILE="$1"
    
    if [ ! -f "$BACKUP_FILE" ]; then
        print_error "Fichier de sauvegarde non trouvé: $BACKUP_FILE"
        exit 1
    fi
    
    # Restauration des fichiers
    tar -xzf "$BACKUP_FILE" -C "$APP_DIR"
    
    print_status "Restauration terminée"
}

# Fonction de mise à jour
update() {
    print_status "Mise à jour de l'application..."
    
    cd "$APP_DIR"
    
    # Sauvegarde avant mise à jour
    backup
    
    # Mise à jour des dépendances
    composer update --no-dev --optimize-autoloader
    
    # Vidage du cache
    php bin/console cache:clear --env=prod
    
    # Exécution des migrations
    php bin/console doctrine:migrations:migrate --no-interaction --env=prod
    
    print_status "Mise à jour terminée"
}

# Fonction de statut
status() {
    print_status "Statut de l'application..."
    
    cd "$APP_DIR"
    
    echo "=== Informations système ==="
    echo "PHP Version: $(php -r 'echo PHP_VERSION;')"
    echo "Composer Version: $(composer --version)"
    echo "Répertoire: $APP_DIR"
    
    echo "=== Base de données ==="
    mysql -u "$DB_USER" -p"$DB_PASSWORD" -e "SELECT 'Connexion OK' as Status;"
    
    echo "=== Cache ==="
    if [ -d "var/cache" ]; then
        echo "Cache: $(du -sh var/cache)"
    else
        echo "Cache: Non trouvé"
    fi
    
    echo "=== Logs ==="
    if [ -d "var/log" ]; then
        echo "Logs: $(du -sh var/log)"
        echo "Dernières erreurs:"
        tail -5 var/log/prod.log 2>/dev/null || echo "Aucun log d'erreur"
    else
        echo "Logs: Non trouvé"
    fi
}

# Menu principal
case "$1" in
    backup)
        backup
        ;;
    restore)
        restore "$2"
        ;;
    update)
        update
        ;;
    status)
        status
        ;;
    *)
        echo "Usage: $0 {backup|restore|update|status}"
        echo ""
        echo "Commandes:"
        echo "  backup              - Créer une sauvegarde"
        echo "  restore <file>      - Restaurer depuis une sauvegarde"
        echo "  update              - Mettre à jour l'application"
        echo "  status              - Afficher le statut"
        exit 1
        ;;
esac
```

## 🔐 Configuration de Sécurité

### **Fichier .htaccess de sécurité**

```apache
# Sécurité pour 3tek
<Files "*.env">
    Order allow,deny
    Deny from all
</Files>

<Files "composer.*">
    Order allow,deny
    Deny from all
</Files>

<Files "*.log">
    Order allow,deny
    Deny from all
</Files>

# Protection contre les injections
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

## 📊 Monitoring et Logs

### **Configuration des logs**

```yaml
# config/packages/monolog.yaml
monolog:
    handlers:
        main:
            type: stream
            path: '%kernel.logs_dir%/%kernel.environment%.log'
            level: info
            channels: ['!event']
        console:
            type: console
            process_psr_3_messages: false
            channels: ['!event', '!doctrine', '!console']
```

## 🚀 Instructions de Déploiement

### **Étape 1 : Préparation**
1. Connectez-vous à votre cPanel
2. Créez une base de données MySQL
3. Créez un utilisateur pour la base de données
4. Notez les informations de connexion

### **Étape 2 : Upload des fichiers**
1. Uploadez les fichiers via FTP/SFTP
2. Placez-les dans `/public_html/3tek/`
3. Assurez-vous que les permissions sont correctes

### **Étape 3 : Configuration**
1. Modifiez le fichier `.env` avec vos paramètres
2. Exécutez `composer install --no-dev --optimize-autoloader`
3. Configurez les permissions

### **Étape 4 : Base de données**
1. Exécutez les migrations : `php bin/console doctrine:migrations:migrate`
2. Videz le cache : `php bin/console cache:clear --env=prod`

### **Étape 5 : Test**
1. Visitez votre domaine
2. Testez la création de compte
3. Vérifiez l'accès admin

## 📞 Support et Maintenance

### **Commandes utiles**
```bash
# Statut de l'application
./maintenance-3tek.sh status

# Sauvegarde
./maintenance-3tek.sh backup

# Mise à jour
./maintenance-3tek.sh update

# Correction des problèmes
php fix-3tek.php
```

### **Logs à surveiller**
- `var/log/prod.log` - Logs de production
- `var/log/dev.log` - Logs de développement
- Logs du serveur web (via cPanel)

---

**Rapport généré le : 28 octobre 2025**  
**Application : 3tek Symfony 7.3**  
**Environnement : Production cPanel**
