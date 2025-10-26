#!/bin/bash

echo "=== SCRIPT DE DÉPLOIEMENT AUTOMATISÉ 3TEK-EUROPE ==="

# Couleurs pour les messages
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Fonction pour afficher les messages
log_info() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

log_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Étape 1: Vérification de l'état Git
log_info "Vérification de l'état Git..."
git status

# Étape 2: Ajout de tous les fichiers
log_info "Ajout de tous les fichiers modifiés..."
git add -A

# Étape 3: Commit avec message descriptif
log_info "Création du commit..."
git commit -m "feat: Déploiement production - Configuration SMTP, corrections admin et optimisations

🚀 DÉPLOIEMENT PRODUCTION CPANEL

✅ Configuration SMTP:
- Identifiants odoip.net configurés
- SSL/TLS sur port 465
- Authentification sécurisée

✅ Corrections Admin:
- Permissions cache Symfony corrigées
- Services publics en mode production
- Scripts d'initialisation améliorés

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

# Étape 4: Push vers le repository distant
log_info "Push vers le repository distant..."
git push origin main

# Étape 5: Vérification du push
if [ $? -eq 0 ]; then
    log_info "✅ Push réussi !"
    log_info "Repository mis à jour avec succès"
else
    log_error "❌ Erreur lors du push"
    exit 1
fi

# Étape 6: Affichage des informations de déploiement
echo ""
log_info "=== INFORMATIONS DE DÉPLOIEMENT ==="
echo ""
echo "📋 Étapes suivantes pour cPanel:"
echo "1. Se connecter à cPanel"
echo "2. Aller dans le répertoire de l'application"
echo "3. Exécuter: git pull origin main"
echo "4. Configurer les variables d'environnement"
echo "5. Exécuter les migrations de base de données"
echo "6. Tester la configuration SMTP"
echo ""
echo "📧 Configuration SMTP à utiliser:"
echo "MAILER_DSN=smtp://noreply%40odoip.net:Ngamba%2D123@mail.odoip.net:465?encryption=ssl"
echo ""
echo "📖 Documentation complète:"
echo "- PROCEDURE_DEPLOIEMENT_CPANEL_COMPLETE.md"
echo "- CONFIGURATION_SMTP_ODOIP.md"
echo ""
log_info "✅ Script de déploiement terminé !"

