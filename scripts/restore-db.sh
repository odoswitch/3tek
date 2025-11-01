#!/bin/bash

# Script de restauration de la base de données 3tek
# Usage: ./restore-db.sh <backup_file.sql[.gz]>

set -e

# Configuration
DB_NAME="3tek"
DB_USER="root"
DB_PASSWORD="ngamba123"
BACKUP_FILE="$1"

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

# Vérification des paramètres
if [ -z "$BACKUP_FILE" ]; then
    print_error "Usage: $0 <backup_file.sql[.gz]>"
    echo ""
    echo "Exemples:"
    echo "  $0 backups/3tek_full_backup_20251028_143022.sql.gz"
    echo "  $0 backups/3tek_data_backup_20251028_143022.sql"
    echo ""
    echo "Sauvegardes disponibles:"
    if [ -d "backups" ]; then
        ls -la backups/*.sql* 2>/dev/null || echo "Aucune sauvegarde trouvée"
    else
        echo "Répertoire backups non trouvé"
    fi
    exit 1
fi

# Vérifier que le fichier existe
if [ ! -f "$BACKUP_FILE" ]; then
    print_error "Fichier de sauvegarde non trouvé: $BACKUP_FILE"
    exit 1
fi

print_header "RESTAURATION BASE DE DONNÉES 3TEK"

print_warning "ATTENTION: Cette opération va écraser la base de données actuelle!"
print_warning "Assurez-vous d'avoir fait une sauvegarde avant de continuer."
echo ""
read -p "Voulez-vous continuer? (y/N): " -n 1 -r
echo ""
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    print_status "Restauration annulée"
    exit 0
fi

# Créer une sauvegarde de sécurité avant restauration
print_status "Création d'une sauvegarde de sécurité..."
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
SAFETY_BACKUP="backups/safety_backup_before_restore_$TIMESTAMP.sql.gz"

mkdir -p backups
mysqldump -u "$DB_USER" -p"$DB_PASSWORD" \
    --single-transaction \
    --routines \
    --triggers \
    --events \
    --add-drop-database \
    --databases "$DB_NAME" | gzip > "$SAFETY_BACKUP"

if [ $? -eq 0 ]; then
    print_status "Sauvegarde de sécurité créée: $SAFETY_BACKUP"
else
    print_error "Erreur lors de la création de la sauvegarde de sécurité"
    exit 1
fi

# Déterminer si le fichier est compressé
if [[ "$BACKUP_FILE" == *.gz ]]; then
    print_status "Décompression du fichier de sauvegarde..."
    TEMP_FILE="/tmp/restore_$(basename "$BACKUP_FILE" .gz)"
    gunzip -c "$BACKUP_FILE" > "$TEMP_FILE"
    RESTORE_FILE="$TEMP_FILE"
else
    RESTORE_FILE="$BACKUP_FILE"
fi

# Vérifier le contenu du fichier
print_status "Vérification du fichier de sauvegarde..."
if grep -q "CREATE DATABASE" "$RESTORE_FILE"; then
    print_status "Sauvegarde complète détectée"
    RESTORE_TYPE="full"
elif grep -q "INSERT INTO" "$RESTORE_FILE"; then
    print_status "Sauvegarde des données détectée"
    RESTORE_TYPE="data"
else
    print_status "Sauvegarde de la structure détectée"
    RESTORE_TYPE="structure"
fi

# Restauration
print_status "Restauration en cours..."
if [ "$RESTORE_TYPE" = "full" ]; then
    # Restauration complète
    mysql -u "$DB_USER" -p"$DB_PASSWORD" < "$RESTORE_FILE"
elif [ "$RESTORE_TYPE" = "data" ]; then
    # Restauration des données uniquement
    mysql -u "$DB_USER" -p"$DB_PASSWORD" "$DB_NAME" < "$RESTORE_FILE"
else
    # Restauration de la structure uniquement
    mysql -u "$DB_USER" -p"$DB_PASSWORD" "$DB_NAME" < "$RESTORE_FILE"
fi

if [ $? -eq 0 ]; then
    print_status "Restauration terminée avec succès!"
else
    print_error "Erreur lors de la restauration"
    print_warning "Vous pouvez restaurer la sauvegarde de sécurité:"
    print_warning "  $0 $SAFETY_BACKUP"
    exit 1
fi

# Nettoyage du fichier temporaire
if [ "$RESTORE_FILE" != "$BACKUP_FILE" ]; then
    rm -f "$RESTORE_FILE"
fi

# Vérification de la restauration
print_status "Vérification de la restauration..."
TABLE_COUNT=$(mysql -u "$DB_USER" -p"$DB_PASSWORD" -e "USE $DB_NAME; SHOW TABLES;" | wc -l)
print_status "Nombre de tables restaurées: $((TABLE_COUNT - 1))"

# Test de connexion
mysql -u "$DB_USER" -p"$DB_PASSWORD" -e "USE $DB_NAME; SELECT 1;" > /dev/null 2>&1
if [ $? -eq 0 ]; then
    print_status "Connexion à la base de données OK"
else
    print_error "Erreur de connexion à la base de données"
    exit 1
fi

print_header "RESTAURATION TERMINÉE"
print_status "Fichier restauré: $BACKUP_FILE"
print_status "Type de restauration: $RESTORE_TYPE"
print_status "Sauvegarde de sécurité: $SAFETY_BACKUP"
print_status "Base de données: $DB_NAME"

print_warning "N'oubliez pas de:"
print_warning "  1. Vider le cache de l'application"
print_warning "  2. Tester l'application"
print_warning "  3. Vérifier les permissions"
