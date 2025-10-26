@echo off
echo === CORRECTION AUTOMATIQUE PERMISSIONS CACHE ===

echo 🔧 Correction des permissions du cache...
docker exec 3tek_php chown -R www-data:www-data /var/www/html/var/cache
docker exec 3tek_php chmod -R 777 /var/www/html/var/cache

echo 📁 Création des répertoires manquants...
docker exec 3tek_php mkdir -p /var/www/html/var/cache/prod/easyadmin
docker exec 3tek_php mkdir -p /var/www/html/var/cache/prod/asset_mapper
docker exec 3tek_php mkdir -p /var/www/html/var/cache/prod/pools/system
docker exec 3tek_php mkdir -p /var/www/html/var/cache/prod/vich_uploader

echo 🔒 Correction des permissions spécifiques...
docker exec 3tek_php chown -R www-data:www-data /var/www/html/var/cache/prod/easyadmin
docker exec 3tek_php chmod -R 777 /var/www/html/var/cache/prod/easyadmin

docker exec 3tek_php chown -R www-data:www-data /var/www/html/var/cache/prod/asset_mapper
docker exec 3tek_php chmod -R 777 /var/www/html/var/cache/prod/asset_mapper

docker exec 3tek_php chown -R www-data:www-data /var/www/html/var/cache/prod/pools
docker exec 3tek_php chmod -R 777 /var/www/html/var/cache/prod/pools

docker exec 3tek_php chown -R www-data:www-data /var/www/html/var/cache/prod/vich_uploader
docker exec 3tek_php chmod -R 777 /var/www/html/var/cache/prod/vich_uploader

echo ✅ Permissions corrigées !
echo 📊 Vérification des permissions :
docker exec 3tek_php ls -la /var/www/html/var/cache/prod/

echo 🌐 Test d'accès à l'application...
curl -s -o nul -w "%%{http_code}" http://localhost:8080/
if %errorlevel% equ 0 (
    echo ✅ Application accessible
) else (
    echo ❌ Application toujours inaccessible
)

curl -s -o nul -w "%%{http_code}" http://localhost:8080/admin
if %errorlevel% equ 0 (
    echo ✅ Admin accessible
) else (
    echo ❌ Admin toujours inaccessible
)

pause

