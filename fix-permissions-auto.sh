#!/bin/bash

echo "=== CORRECTION AUTOMATIQUE DES PERMISSIONS CACHE ==="

# Attendre que le conteneur soit prêt
sleep 5

# Correction des permissions du cache
echo "🔧 Correction des permissions du cache..."
chown -R www-data:www-data /var/www/html/var/cache 2>/dev/null || true
chmod -R 777 /var/www/html/var/cache 2>/dev/null || true

# Création des répertoires manquants
echo "📁 Création des répertoires manquants..."
mkdir -p /var/www/html/var/cache/prod/easyadmin
mkdir -p /var/www/html/var/cache/prod/asset_mapper
mkdir -p /var/www/html/var/cache/prod/pools/system
mkdir -p /var/www/html/var/cache/prod/vich_uploader

# Correction des permissions spécifiques
echo "🔒 Correction des permissions spécifiques..."
chown -R www-data:www-data /var/www/html/var/cache/prod/easyadmin 2>/dev/null || true
chmod -R 777 /var/www/html/var/cache/prod/easyadmin 2>/dev/null || true

chown -R www-data:www-data /var/www/html/var/cache/prod/asset_mapper 2>/dev/null || true
chmod -R 777 /var/www/html/var/cache/prod/asset_mapper 2>/dev/null || true

chown -R www-data:www-data /var/www/html/var/cache/prod/pools 2>/dev/null || true
chmod -R 777 /var/www/html/var/cache/prod/pools 2>/dev/null || true

# Régénération du cache si nécessaire
echo "🔄 Vérification du cache..."
if [ ! -f "/var/www/html/var/cache/prod/easyadmin/application_uses_pretty_urls.txt" ]; then
    echo "🧹 Régénération du cache production..."
    php bin/console cache:clear --env=prod --no-debug 2>/dev/null || true
    php bin/console cache:warmup --env=prod --no-debug 2>/dev/null || true
fi

echo "✅ Permissions corrigées automatiquement !"

