#!/bin/bash

echo "=== CORRECTION AUTOMATIQUE PERMISSIONS CACHE ==="

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

chown -R www-data:www-data /var/www/html/var/cache/prod/vich_uploader 2>/dev/null || true
chmod -R 777 /var/www/html/var/cache/prod/vich_uploader 2>/dev/null || true

# Vérification finale
echo "✅ Permissions corrigées !"
echo "📊 Vérification des permissions :"
ls -la /var/www/html/var/cache/prod/ | head -10

echo "🌐 Test d'accès à l'application..."
if curl -s -o /dev/null -w "%{http_code}" http://localhost:8080/ | grep -q "200"; then
    echo "✅ Application accessible"
else
    echo "❌ Application toujours inaccessible"
fi

if curl -s -o /dev/null -w "%{http_code}" http://localhost:8080/admin | grep -q "200"; then
    echo "✅ Admin accessible"
else
    echo "❌ Admin toujours inaccessible"
fi

