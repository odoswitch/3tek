#!/bin/bash

# Script de déploiement pour cPanel - 3Tek-Europe
# Usage: ./deploy_cpanel.sh

echo "🚀 Démarrage du déploiement 3Tek-Europe..."

# Couleurs pour les messages
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Fonction pour afficher les messages
log_info() {
    echo -e "${GREEN}✓${NC} $1"
}

log_warning() {
    echo -e "${YELLOW}⚠${NC} $1"
}

log_error() {
    echo -e "${RED}✗${NC} $1"
}

# Vérifier si on est dans le bon répertoire
if [ ! -f "composer.json" ]; then
    log_error "Erreur : composer.json non trouvé. Êtes-vous dans le bon répertoire ?"
    exit 1
fi

log_info "Répertoire de travail : $(pwd)"

# 1. Pull des dernières modifications
log_info "1/8 - Récupération des dernières modifications..."
git pull origin main
if [ $? -ne 0 ]; then
    log_error "Erreur lors du git pull"
    exit 1
fi

# 2. Installation des dépendances
log_info "2/8 - Installation des dépendances Composer..."
if command -v composer &> /dev/null; then
    composer install --no-dev --optimize-autoloader --no-interaction
else
    php composer.phar install --no-dev --optimize-autoloader --no-interaction
fi

if [ $? -ne 0 ]; then
    log_error "Erreur lors de l'installation des dépendances"
    exit 1
fi

# 3. Vérifier le fichier .env
log_info "3/8 - Vérification du fichier .env..."
if [ ! -f ".env" ]; then
    log_warning "Fichier .env non trouvé, copie depuis .env.example"
    cp .env.example .env
    log_warning "⚠️  IMPORTANT : Configurez le fichier .env avant de continuer !"
    exit 1
fi

# 4. Migrations de base de données
log_info "4/8 - Exécution des migrations..."
php bin/console doctrine:migrations:migrate --no-interaction --env=prod
if [ $? -ne 0 ]; then
    log_warning "Attention : Erreur lors des migrations (peut être normal si déjà à jour)"
fi

# 5. Nettoyage du cache
log_info "5/8 - Nettoyage du cache..."
rm -rf var/cache/prod/*
php bin/console cache:clear --env=prod --no-warmup
if [ $? -ne 0 ]; then
    log_error "Erreur lors du nettoyage du cache"
    exit 1
fi

# 6. Réchauffement du cache
log_info "6/8 - Réchauffement du cache..."
php bin/console cache:warmup --env=prod
if [ $? -ne 0 ]; then
    log_error "Erreur lors du réchauffement du cache"
    exit 1
fi

# 7. Installation des assets
log_info "7/8 - Installation des assets..."
php bin/console assets:install public --symlink --relative --env=prod
if [ $? -ne 0 ]; then
    log_warning "Attention : Erreur lors de l'installation des assets"
fi

# 8. Permissions
log_info "8/8 - Configuration des permissions..."
chmod -R 755 var/ 2>/dev/null || log_warning "Impossible de modifier les permissions de var/"
chmod -R 755 public/uploads/ 2>/dev/null || log_warning "Impossible de modifier les permissions de public/uploads/"
chmod 644 .env 2>/dev/null || log_warning "Impossible de modifier les permissions de .env"

# Résumé
echo ""
echo "═══════════════════════════════════════════════════════"
log_info "Déploiement terminé avec succès ! 🎉"
echo "═══════════════════════════════════════════════════════"
echo ""
echo "📋 Prochaines étapes :"
echo "  1. Vérifier que le site fonctionne : https://3tek-europe.com"
echo "  2. Tester la connexion admin : https://3tek-europe.com/admin"
echo "  3. Vérifier les logs : tail -f var/log/prod.log"
echo "  4. Tester l'envoi d'emails"
echo ""
echo "📊 Nouvelles fonctionnalités déployées :"
echo "  - Système de logs emails (/admin → Logs Emails)"
echo "  - Pages RGPD (confidentialité, mentions légales)"
echo "  - Timeout de session (30 minutes)"
echo "  - Timezone Europe/Paris"
echo ""
echo "═══════════════════════════════════════════════════════"
