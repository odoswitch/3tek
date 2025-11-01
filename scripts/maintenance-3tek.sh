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
