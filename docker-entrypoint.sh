#!/bin/bash
set -e

# Attendre que la base de données soit prête
echo "Waiting for database to be ready..."
max_attempts=30
attempt=0
until timeout 1 bash -c "cat < /dev/null > /dev/tcp/database/3306" 2>/dev/null; do
    attempt=$((attempt+1))
    if [ $attempt -ge $max_attempts ]; then
        echo "Database connection timeout after $max_attempts attempts"
        echo "Continuing anyway..."
        break
    fi
    echo "Database is unavailable - sleeping (attempt $attempt/$max_attempts)"
    sleep 2
done

echo "Database is up - executing command"

# Créer les répertoires nécessaires s'ils n'existent pas
mkdir -p var/cache var/log var/sessions public/uploads
mkdir -p var/cache/prod var/cache/dev

# Définir les permissions (important pour les volumes Docker)
echo "Setting permissions for var/ and public/uploads..."
chown -R www-data:www-data var public/uploads vendor 2>/dev/null || true
chmod -R 775 var public/uploads 2>/dev/null || true

# S'assurer que les sous-répertoires du cache existent avec les bonnes permissions
mkdir -p var/cache/prod/pools var/cache/dev/pools
mkdir -p var/cache/prod/vich_uploader var/cache/dev/vich_uploader
chown -R www-data:www-data var/cache 2>/dev/null || true
chmod -R 777 var/cache 2>/dev/null || true

# Installer les dépendances si nécessaire
if [ ! -d "vendor" ] || [ ! -f "vendor/autoload.php" ]; then
    echo "Installing Composer dependencies..."
    composer install --no-interaction
fi

# Démarrer PHP-FPM en arrière-plan
echo "Starting PHP-FPM..."
php-fpm -D

# Attendre que PHP-FPM démarre
sleep 2

# Exécuter les tâches d'initialisation en arrière-plan
(
    echo "Running initialization tasks..."
    
    # Vérifier si l'environnement est en production
    if [ "$APP_ENV" = "prod" ]; then
        echo "Running in production mode"
        
        # Nettoyer le cache
        php bin/console cache:clear --no-warmup 2>&1 || true
        php bin/console cache:warmup 2>&1 || true
        
        # Exécuter les migrations
        php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration 2>&1 || true
        
        # Installer les assets
        php bin/console assets:install --no-interaction 2>&1 || true
    else
        echo "Running in development mode"
        
        # Exécuter les migrations
        php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration 2>&1 || true
        
        # Installer les assets
        php bin/console assets:install 2>&1 || true
    fi
    
    echo "Initialization tasks completed"
) &

# Garder le conteneur en vie en surveillant PHP-FPM
echo "Container ready - PHP-FPM is running"
tail -f /dev/null
