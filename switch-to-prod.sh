#!/bin/bash

echo "=== CONFIGURATION MODE PRODUCTION ==="

# Arrêter les conteneurs actuels
echo "🛑 Arrêt des conteneurs actuels..."
docker-compose down

# Configurer les variables d'environnement pour la production
echo "⚙️ Configuration des variables d'environnement..."
export APP_ENV=prod
export APP_DEBUG=false
export APP_SECRET="production-secret-key-$(date +%s)"
export DATABASE_URL="mysql://root:root@db:3306/3tek?serverVersion=8.0&charset=utf8mb4"
export MAILER_DSN="smtp://localhost:1025"
export LOG_LEVEL=warning

# Redémarrer en mode production
echo "🚀 Redémarrage en mode production..."
docker-compose up -d

# Attendre que les services soient prêts
echo "⏳ Attente du démarrage des services..."
sleep 10

# Vider le cache Symfony
echo "🧹 Vidage du cache Symfony..."
docker exec 3tek_php php bin/console cache:clear --env=prod

# Optimiser l'autoloader
echo "🔧 Optimisation de l'autoloader..."
docker exec 3tek_php composer dump-autoload --optimize --no-dev

# Vérifier le statut
echo "📊 Vérification du statut..."
docker exec 3tek_php php bin/console about

echo "✅ Configuration production terminée!"
echo "🌐 Application accessible sur: http://localhost:8080"
echo "📋 Mode: PRODUCTION"
echo "🐛 Debug: DÉSACTIVÉ"

