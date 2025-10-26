@echo off
echo === MAINTENANCE PERMISSIONS CACHE SYMFONY ===

echo 🔧 Correction des permissions du cache...
docker exec 3tek_php chown -R www-data:www-data /var/www/html/var/cache
docker exec 3tek_php chmod -R 777 /var/www/html/var/cache

echo 📁 Création des répertoires manquants...
docker exec 3tek_php mkdir -p /var/www/html/var/cache/prod/asset_mapper
docker exec 3tek_php mkdir -p /var/www/html/var/cache/prod/pools/system
docker exec 3tek_php mkdir -p /var/www/html/var/cache/prod/vich_uploader

echo 🔒 Correction des permissions spécifiques...
docker exec 3tek_php chown -R www-data:www-data /var/www/html/var/cache/prod/asset_mapper
docker exec 3tek_php chmod -R 777 /var/www/html/var/cache/prod/asset_mapper

docker exec 3tek_php chown -R www-data:www-data /var/www/html/var/cache/prod/pools
docker exec 3tek_php chmod -R 777 /var/www/html/var/cache/prod/pools

echo 🧹 Nettoyage du cache corrompu...
docker exec 3tek_php rm -rf /var/www/html/var/cache/prod/pools/system/*

echo 🔄 Régénération du cache production...
docker exec 3tek_php php bin/console cache:clear --env=prod --no-debug
docker exec 3tek_php php bin/console cache:warmup --env=prod --no-debug

echo ✅ Maintenance terminée !
echo 🌐 L'admin devrait maintenant fonctionner correctement.

echo 🧪 Test d'accès admin...
curl -s -o nul -w "%%{http_code}" http://localhost:8080/admin/user
if %errorlevel% equ 0 (
    echo ✅ Admin user accessible
) else (
    echo ❌ Admin user toujours inaccessible
)

pause

