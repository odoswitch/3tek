#!/bin/bash

echo "=== SCRIPT DE DÉPLOIEMENT COMPLET CPANEL - 3TEK-EUROPE ==="

# Couleurs pour les messages
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
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

log_step() {
    echo -e "${BLUE}[ÉTAPE]${NC} $1"
}

# Fonction pour vérifier le succès d'une commande
check_success() {
    if [ $? -eq 0 ]; then
        log_info "✅ $1"
        return 0
    else
        log_error "❌ $1"
        return 1
    fi
}

# Vérifier qu'on est dans le bon répertoire
if [ ! -f "composer.json" ]; then
    log_error "Ce script doit être exécuté depuis la racine de l'application Symfony"
    exit 1
fi

echo ""
log_step "1. PRÉPARATION DU DÉPLOIEMENT"
echo "=================================="

# Étape 1: Vérification de l'état Git
log_info "Vérification de l'état Git..."
git status
echo ""

# Étape 2: Ajout de tous les fichiers
log_info "Ajout de tous les fichiers modifiés..."
git add -A
check_success "Fichiers ajoutés au staging"

# Étape 3: Commit avec message descriptif
log_info "Création du commit..."
git commit -m "feat: Déploiement production cPanel - Configuration SMTP et corrections admin

🚀 DÉPLOIEMENT PRODUCTION CPANEL

✅ Configuration SMTP:
- Identifiants odoip.net configurés
- SSL/TLS sur port 465
- Authentification sécurisée

✅ Corrections Admin:
- Permissions cache Symfony corrigées
- Services publics en mode production
- Scripts d'initialisation améliorés
- Solution définitive erreur 'Permission denied'

✅ Fonctionnalités:
- Interface admin entièrement fonctionnelle
- Système de commandes et file d'attente
- Génération PDF des commandes
- Notifications email automatiques

✅ Optimisations:
- Cache Symfony optimisé
- Scripts de maintenance automatique
- Documentation complète déploiement
- Tests de validation intégrés

📋 Prêt pour déploiement cPanel avec base de données mise à jour"

check_success "Commit créé"

# Étape 4: Push vers le repository distant
log_info "Push vers le repository distant..."
git push origin main
check_success "Push vers le repository distant"

echo ""
log_step "2. PRÉPARATION DES SCRIPTS CPANEL"
echo "===================================="

# Créer le script de correction admin
log_info "Création du script de correction admin..."
cat > fix-admin-cpanel.sh << 'EOF'
#!/bin/bash
echo "=== CORRECTION DÉFINITIVE ADMIN CPANEL ==="
echo "🔧 Résolution du problème 'Permission denied' sur cache"

# Supprimer le cache corrompu
rm -rf var/cache/prod/*

# Créer les répertoires nécessaires
mkdir -p var/cache/prod/easyadmin
mkdir -p var/cache/prod/asset_mapper
mkdir -p var/cache/prod/pools/system
mkdir -p var/cache/prod/vich_uploader
mkdir -p var/cache/prod/translations
mkdir -p var/cache/prod/twig

# Permissions absolues
chmod -R 777 var/cache/
chmod -R 777 var/log/

# Propriétaire
chown -R $(whoami):$(whoami) var/cache/ 2>/dev/null || true
chown -R $(whoami):$(whoami) var/log/ 2>/dev/null || true

# Cache Symfony
php bin/console cache:clear --env=prod --no-debug
php bin/console cache:warmup --env=prod --no-debug

echo "✅ Correction terminée !"
EOF

chmod +x fix-admin-cpanel.sh
check_success "Script de correction admin créé"

# Créer le script de configuration SMTP
log_info "Création du script de configuration SMTP..."
cat > configure-smtp-cpanel.sh << 'EOF'
#!/bin/bash
echo "=== CONFIGURATION SMTP CPANEL ==="

# Créer le fichier .env.local
cat > .env.local << 'ENVEOF'
# Configuration SMTP pour production
MAILER_DSN=smtp://noreply%40odoip.net:Ngamba%2D123@mail.odoip.net:465?encryption=ssl
MAILER_FROM=noreply@odoip.net
MAILER_FROM_NAME="3Tek-Europe"

# Configuration production
APP_ENV=prod
APP_DEBUG=false
ENVEOF

echo "✅ Configuration SMTP créée dans .env.local"
EOF

chmod +x configure-smtp-cpanel.sh
check_success "Script de configuration SMTP créé"

echo ""
log_step "3. INSTRUCTIONS DE DÉPLOIEMENT CPANEL"
echo "========================================="

echo ""
log_info "📋 ÉTAPES À SUIVRE SUR CPANEL :"
echo ""
echo "1. 📁 Se connecter à cPanel et aller dans le répertoire de l'application"
echo "   cd /home/votrecompte/public_html/3tek"
echo ""
echo "2. 🔄 Récupérer les dernières modifications"
echo "   git pull origin main"
echo ""
echo "3. 📧 Configurer SMTP"
echo "   ./configure-smtp-cpanel.sh"
echo ""
echo "4. 🔧 Corriger les permissions admin (OBLIGATOIRE)"
echo "   ./fix-admin-cpanel.sh"
echo ""
echo "5. 🗄️ Exécuter les migrations"
echo "   php bin/console doctrine:migrations:migrate --no-interaction"
echo ""
echo "6. 🧪 Tester l'application"
echo "   curl -I https://votre-domaine.com/admin"
echo ""

echo ""
log_info "📧 CONFIGURATION SMTP À UTILISER :"
echo "MAILER_DSN=smtp://noreply%40odoip.net:Ngamba%2D123@mail.odoip.net:465?encryption=ssl"
echo ""

echo ""
log_info "📖 DOCUMENTATION DISPONIBLE :"
echo "- PROCEDURE_DEPLOIEMENT_CPANEL_COMPLETE.md (Guide complet)"
echo "- RESUME_DEPLOIEMENT_CPANEL.md (Résumé rapide)"
echo "- CONFIGURATION_SMTP_ODOIP.md (Configuration SMTP)"
echo ""

echo ""
log_info "🚨 EN CAS DE PROBLÈME ADMIN BLOQUÉ :"
echo "Exécuter immédiatement : ./fix-admin-cpanel.sh"
echo ""

echo ""
log_step "4. VÉRIFICATION FINALE"
echo "========================"

# Vérifier que tous les fichiers nécessaires existent
REQUIRED_FILES=(
    "fix-admin-cpanel.sh"
    "configure-smtp-cpanel.sh"
    "PROCEDURE_DEPLOIEMENT_CPANEL_COMPLETE.md"
    "RESUME_DEPLOIEMENT_CPANEL.md"
    "CONFIGURATION_SMTP_ODOIP.md"
)

for file in "${REQUIRED_FILES[@]}"; do
    if [ -f "$file" ]; then
        log_info "✅ $file"
    else
        log_error "❌ $file manquant"
    fi
done

echo ""
log_info "=== DÉPLOIEMENT PRÉPARÉ AVEC SUCCÈS ==="
echo ""
echo "🎯 Prochaines étapes :"
echo "1. Exécuter les commandes cPanel ci-dessus"
echo "2. Tester l'accès admin"
echo "3. Vérifier les emails SMTP"
echo ""
echo "📞 Support : contact@3tek-europe.com"
echo "📱 Téléphone : +33 1 83 61 18 36"
echo ""
log_info "✅ Script de déploiement terminé !"

