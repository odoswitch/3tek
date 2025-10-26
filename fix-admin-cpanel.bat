@echo off
echo === CORRECTION DEFINITIVE ADMIN CPANEL ===
echo 🔧 Resolution du probleme 'Permission denied' sur cache

echo [INFO] Verification du repertoire...
if not exist "composer.json" (
    echo [ERROR] Ce script doit etre execute depuis la racine de l'application Symfony
    pause
    exit /b 1
)

echo [INFO] Debut de la correction des permissions cache...

echo [INFO] Suppression du cache corrompu...
if exist "var\cache\prod" rmdir /s /q "var\cache\prod" 2>nul

echo [INFO] Creation des repertoires de cache...
mkdir "var\cache\prod\easyadmin" 2>nul
mkdir "var\cache\prod\asset_mapper" 2>nul
mkdir "var\cache\prod\pools\system" 2>nul
mkdir "var\cache\prod\vich_uploader" 2>nul
mkdir "var\cache\prod\translations" 2>nul
mkdir "var\cache\prod\twig" 2>nul

echo [INFO] ✅ Repertoires crees

echo [INFO] Application des permissions absolues...
icacls "var\cache" /grant Everyone:F /T 2>nul
icacls "var\log" /grant Everyone:F /T 2>nul

echo [INFO] ✅ Permissions appliquees

echo [INFO] Vidage du cache Symfony...
php bin/console cache:clear --env=prod --no-debug

if %errorlevel% neq 0 (
    echo [ERROR] Erreur lors du vidage du cache
    pause
    exit /b 1
)

echo [INFO] Rechauffement du cache Symfony...
php bin/console cache:warmup --env=prod --no-debug

if %errorlevel% neq 0 (
    echo [ERROR] Erreur lors du rechauffement du cache
    pause
    exit /b 1
)

echo [INFO] ✅ Cache Symfony vide et rechauffe

echo.
echo [INFO] === CORRECTION TERMINEE ===
echo.
echo 📋 Resume des actions effectuees:
echo ✅ Cache corrompu supprime
echo ✅ Repertoires de cache recrees
echo ✅ Permissions appliquees
echo ✅ Cache Symfony vide et rechauffe
echo.
echo 🔍 Si le probleme persiste:
echo 1. Verifiez les logs: type var\log\prod.log
echo 2. Testez l'acces admin dans votre navigateur
echo 3. Contactez le support: contact@3tek-europe.com
echo.
echo [INFO] ✅ Correction terminee avec succes !
pause

