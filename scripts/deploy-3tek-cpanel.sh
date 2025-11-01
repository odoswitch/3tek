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
