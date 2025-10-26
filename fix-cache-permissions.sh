#!/bin/bash

echo "=== CORRECTION PERMISSIONS CACHE SYMFONY ==="

echo "🔧 Correction des permissions du cache..."
docker exec 3tek_php chown -R www-data:www-data /var/www/html/var/cache
docker exec 3tek_php chmod -R 755 /var/www/html/var/cache

echo "🧹 Suppression du cache corrompu..."
docker exec 3tek_php rm -rf /var/www/html/var/cache/prod

echo "🔄 Régénération du cache production..."
docker exec 3tek_php php bin/console cache:clear --env=prod --no-debug
docker exec 3tek_php php bin/console cache:warmup --env=prod --no-debug

echo "✅ Permissions corrigées et cache régénéré !"
echo "🌐 L'admin devrait maintenant fonctionner correctement."

