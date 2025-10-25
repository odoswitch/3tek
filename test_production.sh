#!/bin/bash

# Script de test en mode production
# Usage: ./test_production.sh

echo "🚀 Test de l'application en mode production"
echo "=========================================="
echo ""

# Couleurs
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Fonction de test
test_command() {
    local command=$1
    local description=$2
    
    echo -n "⏳ $description... "
    
    if eval "$command" > /dev/null 2>&1; then
        echo -e "${GREEN}✓ OK${NC}"
        return 0
    else
        echo -e "${RED}✗ ERREUR${NC}"
        return 1
    fi
}

# 1. Vérifier l'environnement
echo "📋 Vérification de l'environnement"
echo "-----------------------------------"

if grep -q "APP_ENV=prod" .env; then
    echo -e "${GREEN}✓${NC} Mode production activé"
else
    echo -e "${RED}✗${NC} Mode production NON activé"
    echo -e "${YELLOW}⚠${NC}  Modifier APP_ENV=prod dans .env"
fi

if grep -q "APP_DEBUG=0" .env; then
    echo -e "${GREEN}✓${NC} Debug désactivé"
else
    echo -e "${YELLOW}⚠${NC}  Debug devrait être à 0 en production"
fi

echo ""

# 2. Tests Symfony
echo "🔧 Tests Symfony"
echo "----------------"

test_command "docker compose exec php php bin/console about --env=prod" "Informations Symfony"
test_command "docker compose exec php php bin/console doctrine:schema:validate --env=prod" "Validation schéma base de données"
test_command "docker compose exec php php bin/console debug:router --env=prod | head -n 5" "Routes disponibles"

echo ""

# 3. Tests cache
echo "💾 Tests cache"
echo "--------------"

echo "⏳ Nettoyage du cache..."
docker compose exec php php bin/console cache:clear --env=prod --no-warmup
echo -e "${GREEN}✓${NC} Cache nettoyé"

echo "⏳ Réchauffement du cache..."
docker compose exec php php bin/console cache:warmup --env=prod
echo -e "${GREEN}✓${NC} Cache réchauffé"

echo ""

# 4. Tests base de données
echo "🗄️  Tests base de données"
echo "-------------------------"

test_command "docker compose exec php php bin/console doctrine:query:sql 'SELECT 1' --env=prod" "Connexion base de données"
test_command "docker compose exec php php bin/console doctrine:query:sql 'SELECT COUNT(*) FROM user' --env=prod" "Comptage utilisateurs"
test_command "docker compose exec php php bin/console doctrine:query:sql 'SELECT COUNT(*) FROM lot' --env=prod" "Comptage lots"

echo ""

# 5. Tests permissions
echo "🔐 Tests permissions"
echo "--------------------"

if [ -w "var/cache" ]; then
    echo -e "${GREEN}✓${NC} var/cache accessible en écriture"
else
    echo -e "${RED}✗${NC} var/cache NON accessible en écriture"
fi

if [ -w "var/log" ]; then
    echo -e "${GREEN}✓${NC} var/log accessible en écriture"
else
    echo -e "${RED}✗${NC} var/log NON accessible en écriture"
fi

if [ -w "public/uploads" ]; then
    echo -e "${GREEN}✓${NC} public/uploads accessible en écriture"
else
    echo -e "${RED}✗${NC} public/uploads NON accessible en écriture"
fi

echo ""

# 6. Tests fichiers critiques
echo "📁 Tests fichiers critiques"
echo "---------------------------"

files=(".env" "composer.json" "public/index.php" "config/packages/doctrine.yaml")

for file in "${files[@]}"; do
    if [ -f "$file" ]; then
        echo -e "${GREEN}✓${NC} $file existe"
    else
        echo -e "${RED}✗${NC} $file MANQUANT"
    fi
done

echo ""

# 7. Tests sécurité
echo "🔒 Tests sécurité"
echo "-----------------"

if grep -q "APP_SECRET=31a1dc0a4d2977405a7293b0c56c06fa" .env; then
    echo -e "${YELLOW}⚠${NC}  APP_SECRET par défaut détecté - CHANGER EN PRODUCTION"
else
    echo -e "${GREEN}✓${NC} APP_SECRET personnalisé"
fi

if [ -f ".env.local" ]; then
    echo -e "${YELLOW}⚠${NC}  .env.local existe - vérifier qu'il n'est pas commité"
fi

echo ""

# 8. Tests URLs (si serveur lancé)
echo "🌐 Tests URLs"
echo "-------------"

if curl -s http://localhost:8080 > /dev/null 2>&1; then
    echo -e "${GREEN}✓${NC} Serveur accessible"
    
    # Test page d'accueil
    if curl -s http://localhost:8080 | grep -q "3Tek"; then
        echo -e "${GREEN}✓${NC} Page d'accueil OK"
    else
        echo -e "${RED}✗${NC} Page d'accueil KO"
    fi
    
    # Test page 404
    if curl -s http://localhost:8080/page-inexistante | grep -q "404"; then
        echo -e "${GREEN}✓${NC} Page 404 personnalisée OK"
    else
        echo -e "${YELLOW}⚠${NC}  Page 404 à vérifier"
    fi
else
    echo -e "${YELLOW}⚠${NC}  Serveur non accessible (normal si non démarré)"
fi

echo ""

# 9. Résumé
echo "📊 Résumé"
echo "========="
echo ""
echo -e "${GREEN}✓${NC} Tests terminés"
echo ""
echo "Actions recommandées avant déploiement :"
echo "1. Vérifier que APP_SECRET est unique"
echo "2. Configurer les vraies informations de base de données"
echo "3. Configurer le MAILER_DSN avec les vrais identifiants"
echo "4. Tester l'envoi d'emails"
echo "5. Vérifier les pages d'erreur (404, 500)"
echo "6. Faire un backup de la base de données"
echo ""
echo "Pour revenir en mode développement :"
echo "  Changer APP_ENV=dev dans .env"
echo "  Puis: docker compose exec php php bin/console cache:clear"
echo ""
