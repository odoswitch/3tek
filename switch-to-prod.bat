@echo off
echo === CONFIGURATION MODE PRODUCTION ===

REM Arrêter les conteneurs actuels
echo 🛑 Arrêt des conteneurs actuels...
docker-compose down

REM Configurer les variables d'environnement pour la production
echo ⚙️ Configuration des variables d'environnement...
set APP_ENV=prod
set APP_DEBUG=false
set APP_SECRET=production-secret-key-%RANDOM%
set DATABASE_URL=mysql://root:root@db:3306/3tek?serverVersion=8.0&charset=utf8mb4
set MAILER_DSN=smtp://localhost:1025
set LOG_LEVEL=warning

REM Redémarrer en mode production
echo 🚀 Redémarrage en mode production...
docker-compose up -d

REM Attendre que les services soient prêts
echo ⏳ Attente du démarrage des services...
timeout /t 15 /nobreak >nul

REM Vider le cache Symfony
echo 🧹 Vidage du cache Symfony...
docker exec 3tek_php php bin/console cache:clear --env=prod

REM Optimiser l'autoloader
echo 🔧 Optimisation de l'autoloader...
docker exec 3tek_php composer dump-autoload --optimize --no-dev

REM Vérifier le statut
echo 📊 Vérification du statut...
docker exec 3tek_php php bin/console about

echo ✅ Configuration production terminée!
echo 🌐 Application accessible sur: http://localhost:8080
echo 📋 Mode: PRODUCTION
echo 🐛 Debug: DÉSACTIVÉ
pause

