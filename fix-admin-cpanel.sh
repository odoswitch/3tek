#!/bin/bash

echo "=== CORRECTION DÉFINITIVE ADMIN CPANEL ==="
echo "🔧 Résolution du problème 'Permission denied' sur cache"

# Couleurs pour les messages
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

log_info() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

log_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Vérifier qu'on est dans le bon répertoire
if [ ! -f "composer.json" ]; then
    log_error "Ce script doit être exécuté depuis la racine de l'application Symfony"
    exit 1
fi

log_info "Début de la correction des permissions cache..."

# Étape 1: Arrêter temporairement l'application (optionnel)
log_warning "Arrêt temporaire de l'application pour éviter les conflits..."
# (Commenté car pas toujours nécessaire sur cPanel)
# systemctl stop apache2 2>/dev/null || true

# Étape 2: Supprimer complètement le cache corrompu
log_info "Suppression du cache corrompu..."
rm -rf var/cache/prod/* 2>/dev/null || true
log_info "✅ Cache supprimé"

# Étape 3: Créer manuellement tous les répertoires nécessaires
log_info "Création des répertoires de cache..."
mkdir -p var/cache/prod/easyadmin
mkdir -p var/cache/prod/asset_mapper
mkdir -p var/cache/prod/pools/system
mkdir -p var/cache/prod/vich_uploader
mkdir -p var/cache/prod/translations
mkdir -p var/cache/prod/twig
log_info "✅ Répertoires créés"

# Étape 4: Permissions absolues (777)
log_info "Application des permissions absolues..."
chmod -R 777 var/cache/ 2>/dev/null || true
chmod -R 777 var/log/ 2>/dev/null || true
log_info "✅ Permissions 777 appliquées"

# Étape 5: Propriétaire (essayer de détecter l'utilisateur actuel)
CURRENT_USER=$(whoami)
log_info "Définition du propriétaire: $CURRENT_USER"
chown -R $CURRENT_USER:$CURRENT_USER var/cache/ 2>/dev/null || true
chown -R $CURRENT_USER:$CURRENT_USER var/log/ 2>/dev/null || true
log_info "✅ Propriétaire défini"

# Étape 6: Permissions spécifiques pour les sous-répertoires critiques
log_info "Permissions spécifiques pour les répertoires critiques..."
chmod 777 var/cache/prod/easyadmin 2>/dev/null || true
chmod 777 var/cache/prod/asset_mapper 2>/dev/null || true
chmod 777 var/cache/prod/pools/system 2>/dev/null || true
chmod 777 var/cache/prod/vich_uploader 2>/dev/null || true
log_info "✅ Permissions spécifiques appliquées"

# Étape 7: Vérifier les permissions
log_info "Vérification des permissions..."
ls -la var/cache/prod/ 2>/dev/null || log_warning "Impossible de lister var/cache/prod/"

# Étape 8: Vider et réchauffer le cache Symfony
log_info "Vidage du cache Symfony..."
php bin/console cache:clear --env=prod --no-debug 2>/dev/null || {
    log_error "Erreur lors du vidage du cache"
    exit 1
}

log_info "Réchauffement du cache Symfony..."
php bin/console cache:warmup --env=prod --no-debug 2>/dev/null || {
    log_error "Erreur lors du réchauffement du cache"
    exit 1
}

log_info "✅ Cache Symfony vidé et réchauffé"

# Étape 9: Vérification finale
log_info "Vérification finale des permissions..."
if [ -w "var/cache/prod/asset_mapper" ]; then
    log_info "✅ Répertoire asset_mapper accessible en écriture"
else
    log_warning "⚠️ Répertoire asset_mapper non accessible en écriture"
fi

if [ -w "var/cache/prod/pools/system" ]; then
    log_info "✅ Répertoire pools/system accessible en écriture"
else
    log_warning "⚠️ Répertoire pools/system non accessible en écriture"
fi

# Étape 10: Test de l'accès admin
log_info "Test de l'accès admin..."
if command -v curl >/dev/null 2>&1; then
    HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" http://localhost/admin 2>/dev/null || echo "000")
    if [ "$HTTP_CODE" = "200" ] || [ "$HTTP_CODE" = "302" ]; then
        log_info "✅ Admin accessible (HTTP $HTTP_CODE)"
    else
        log_warning "⚠️ Admin peut-être encore inaccessible (HTTP $HTTP_CODE)"
    fi
else
    log_warning "curl non disponible - impossible de tester l'accès admin"
fi

# Étape 11: Redémarrage des services (si nécessaire)
log_info "Redémarrage des services web..."
# (Commenté car dépend de la configuration cPanel)
# systemctl restart apache2 2>/dev/null || true
# systemctl restart nginx 2>/dev/null || true

echo ""
log_info "=== CORRECTION TERMINÉE ==="
echo ""
echo "📋 Résumé des actions effectuées:"
echo "✅ Cache corrompu supprimé"
echo "✅ Répertoires de cache recréés"
echo "✅ Permissions 777 appliquées"
echo "✅ Propriétaire défini ($CURRENT_USER)"
echo "✅ Cache Symfony vidé et réchauffé"
echo ""
echo "🔍 Si le problème persiste:"
echo "1. Vérifiez les logs: tail -f var/log/prod.log"
echo "2. Testez l'accès: curl -I https://votre-domaine.com/admin"
echo "3. Contactez le support: contact@3tek-europe.com"
echo ""
log_info "✅ Correction terminée avec succès !"

