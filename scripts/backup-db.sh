#!/bin/bash

# Script de sauvegarde de la base de données 3tek
# Usage: ./backup-db.sh

set -e

# Configuration
DB_NAME="3tek"
DB_USER="root"
DB_PASSWORD="ngamba123"
BACKUP_DIR="/opt/docker/3tek/backups"
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")

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

print_header() {
    echo -e "${BLUE}================================${NC}"
    echo -e "${BLUE} $1${NC}"
    echo -e "${BLUE}================================${NC}"
}

# Créer le répertoire de sauvegarde
mkdir -p "$BACKUP_DIR"

print_header "SAUVEGARDE BASE DE DONNÉES 3TEK"

# Sauvegarde complète
print_status "Création de la sauvegarde complète..."
mysqldump -u "$DB_USER" -p"$DB_PASSWORD" \
    --single-transaction \
    --routines \
    --triggers \
    --events \
    --add-drop-database \
    --databases "$DB_NAME" > "$BACKUP_DIR/3tek_full_backup_$TIMESTAMP.sql"

if [ $? -eq 0 ]; then
    print_status "Sauvegarde complète créée: $BACKUP_DIR/3tek_full_backup_$TIMESTAMP.sql"
else
    print_error "Erreur lors de la sauvegarde complète"
    exit 1
fi

# Sauvegarde des données uniquement
print_status "Création de la sauvegarde des données..."
mysqldump -u "$DB_USER" -p"$DB_PASSWORD" \
    --no-create-info \
    --no-create-db \
    --single-transaction \
    "$DB_NAME" > "$BACKUP_DIR/3tek_data_backup_$TIMESTAMP.sql"

if [ $? -eq 0 ]; then
    print_status "Sauvegarde des données créée: $BACKUP_DIR/3tek_data_backup_$TIMESTAMP.sql"
else
    print_error "Erreur lors de la sauvegarde des données"
    exit 1
fi

# Sauvegarde de la structure uniquement
print_status "Création de la sauvegarde de la structure..."
mysqldump -u "$DB_USER" -p"$DB_PASSWORD" \
    --no-data \
    --routines \
    --triggers \
    --events \
    "$DB_NAME" > "$BACKUP_DIR/3tek_structure_backup_$TIMESTAMP.sql"

if [ $? -eq 0 ]; then
    print_status "Sauvegarde de la structure créée: $BACKUP_DIR/3tek_structure_backup_$TIMESTAMP.sql"
else
    print_error "Erreur lors de la sauvegarde de la structure"
    exit 1
fi

# Compression des sauvegardes
print_status "Compression des sauvegardes..."
gzip "$BACKUP_DIR/3tek_full_backup_$TIMESTAMP.sql"
gzip "$BACKUP_DIR/3tek_data_backup_$TIMESTAMP.sql"
gzip "$BACKUP_DIR/3tek_structure_backup_$TIMESTAMP.sql"

# Affichage des informations
print_header "SAUVEGARDE TERMINÉE"
print_status "Répertoire: $BACKUP_DIR"
print_status "Sauvegardes créées:"
echo "  - 3tek_full_backup_$TIMESTAMP.sql.gz (Sauvegarde complète)"
echo "  - 3tek_data_backup_$TIMESTAMP.sql.gz (Données uniquement)"
echo "  - 3tek_structure_backup_$TIMESTAMP.sql.gz (Structure uniquement)"

# Taille des fichiers
print_status "Taille des sauvegardes:"
ls -lh "$BACKUP_DIR"/*_$TIMESTAMP.sql.gz

# Nettoyage des anciennes sauvegardes (garder les 10 dernières)
print_status "Nettoyage des anciennes sauvegardes..."
cd "$BACKUP_DIR"
ls -t 3tek_full_backup_*.sql.gz | tail -n +11 | xargs -r rm
ls -t 3tek_data_backup_*.sql.gz | tail -n +11 | xargs -r rm
ls -t 3tek_structure_backup_*.sql.gz | tail -n +11 | xargs -r rm

print_status "Sauvegarde terminée avec succès!"
