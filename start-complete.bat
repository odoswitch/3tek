@echo off
echo ========================================
echo  Demarrage Complet - 3TEK
echo ========================================
echo.

REM Arreter les conteneurs existants
echo [1/6] Arret des conteneurs existants...
docker compose down 2>nul
echo.

REM Nettoyer les conteneurs orphelins
echo [2/6] Nettoyage...
docker compose down --remove-orphans 2>nul
echo.

REM Demarrer les conteneurs
echo [3/6] Demarrage des conteneurs...
docker compose -f compose.yaml -f compose.override.yaml up -d
if errorlevel 1 (
    echo [ERREUR] Echec du demarrage
    echo.
    echo Verifiez que Docker Desktop est lance
    pause
    exit /b 1
)
echo.

REM Attendre que les conteneurs soient prets
echo [4/6] Attente du demarrage (30 secondes)...
timeout /t 30 /nobreak
echo.

REM Verifier l'etat
echo [5/6] Etat des conteneurs:
docker compose ps
echo.

REM Installer les dependances si necessaire
echo [6/6] Installation des dependances...
docker compose exec -T php composer install --no-interaction 2>nul
docker compose exec -T php php bin/console doctrine:database:create --if-not-exists --no-interaction 2>nul
docker compose exec -T php php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration 2>nul
docker compose exec -T php php bin/console assets:install --no-interaction 2>nul
docker compose exec -T php php bin/console cache:clear --no-interaction 2>nul
echo.

echo ========================================
echo  Demarrage termine!
echo ========================================
echo.
echo Application:  http://localhost:8080
echo PhpMyAdmin:   http://localhost:8081
echo Mailpit:      http://localhost:8025
echo.
echo Si l'application ne repond pas, verifiez les logs:
echo   docker compose logs
echo.
pause
