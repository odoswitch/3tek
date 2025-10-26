#!/bin/bash

echo "=== CORRECTION CONFIGURATION BASE DE DONNÉES ===\n"

# Arrêter les conteneurs
echo "🛑 Arrêt des conteneurs..."
docker-compose down

# Configurer les bonnes variables d'environnement
echo "⚙️ Configuration des variables d'environnement..."
set MYSQL_DATABASE=db_3tek
set MYSQL_ROOT_PASSWORD=ngamba123
set MYSQL_USER=app
set MYSQL_PASSWORD=!ChangeMe!
set DATABASE_URL=mysql://root:ngamba123@3tek-database-1:3306/db_3tek?serverVersion=8.0&charset=utf8mb4

# Redémarrer les conteneurs
echo "🚀 Redémarrage des conteneurs..."
docker-compose up -d

# Attendre le démarrage
echo "⏳ Attente du démarrage..."
timeout /t 15 /nobreak >nul

# Tester la connexion Symfony
echo "🔧 Test de la connexion Symfony..."
docker exec 3tek_php php bin/console doctrine:database:create --if-not-exists

# Vider le cache
echo "🧹 Vidage du cache..."
docker exec 3tek_php php bin/console cache:clear --env=prod

echo "✅ Configuration corrigée!"
echo "🌐 Application accessible sur: http://localhost:8080"

