@echo off
echo ========================================
echo  Demarrage de l'environnement 3TEK
echo ========================================
echo.

REM Verifier si Docker est installe
docker --version >nul 2>&1
if errorlevel 1 (
    echo [ERREUR] Docker n'est pas installe ou n'est pas dans le PATH
    echo Veuillez installer Docker Desktop: https://www.docker.com/products/docker-desktop
    pause
    exit /b 1
)

echo [OK] Docker est installe
echo.

REM Verifier si Docker est en cours d'execution
docker info >nul 2>&1
if errorlevel 1 (
    echo [ERREUR] Docker n'est pas en cours d'execution
    echo Veuillez demarrer Docker Desktop
    pause
    exit /b 1
)

echo [OK] Docker est en cours d'execution
echo.

REM Verifier si le fichier .env existe
if not exist .env (
    echo [INFO] Fichier .env non trouve, copie depuis .env.example
    copy .env.example .env
)

echo [INFO] Demarrage des conteneurs Docker...
docker compose -f compose.yaml -f compose.override.yaml up -d

if errorlevel 1 (
    echo [ERREUR] Echec du demarrage des conteneurs
    pause
    exit /b 1
)

echo.
echo ========================================
echo  Environnement demarre avec succes!
echo ========================================
echo.
echo Application web:  http://localhost:8080
echo PhpMyAdmin:       http://localhost:8081
echo Mailpit:          http://localhost:8025
echo.
echo Pour voir les logs: docker compose logs -f
echo Pour arreter:      docker compose down
echo.
pause
