@echo off
echo ========================================
echo  Verification de l'environnement Docker
echo ========================================
echo.

set ERROR=0

REM Verifier Docker
echo [1/5] Verification de Docker...
docker --version >nul 2>&1
if errorlevel 1 (
    echo [ERREUR] Docker n'est pas installe
    echo Installez Docker Desktop: https://www.docker.com/products/docker-desktop
    set ERROR=1
) else (
    docker --version
    echo [OK] Docker est installe
)
echo.

REM Verifier Docker Compose
echo [2/5] Verification de Docker Compose...
docker compose version >nul 2>&1
if errorlevel 1 (
    echo [ERREUR] Docker Compose n'est pas disponible
    set ERROR=1
) else (
    docker compose version
    echo [OK] Docker Compose est disponible
)
echo.

REM Verifier si Docker est en cours d'execution
echo [3/5] Verification que Docker est en cours d'execution...
docker info >nul 2>&1
if errorlevel 1 (
    echo [ERREUR] Docker n'est pas en cours d'execution
    echo Veuillez demarrer Docker Desktop
    set ERROR=1
) else (
    echo [OK] Docker est en cours d'execution
)
echo.

REM Verifier les fichiers necessaires
echo [4/5] Verification des fichiers de configuration...
set FILES_OK=1

if not exist Dockerfile (
    echo [ERREUR] Dockerfile manquant
    set FILES_OK=0
    set ERROR=1
)

if not exist compose.yaml (
    echo [ERREUR] compose.yaml manquant
    set FILES_OK=0
    set ERROR=1
)

if not exist docker-entrypoint.sh (
    echo [ERREUR] docker-entrypoint.sh manquant
    set FILES_OK=0
    set ERROR=1
)

if not exist .env (
    echo [ATTENTION] Fichier .env manquant, creation depuis .env.example
    if exist .env.example (
        copy .env.example .env >nul
        echo [OK] Fichier .env cree
    ) else (
        echo [ERREUR] .env.example manquant
        set FILES_OK=0
        set ERROR=1
    )
)

if %FILES_OK%==1 (
    echo [OK] Tous les fichiers de configuration sont presents
)
echo.

REM Verifier les ports
echo [5/5] Verification des ports...
netstat -an | findstr ":8080" >nul
if not errorlevel 1 (
    echo [ATTENTION] Le port 8080 est deja utilise
    echo Vous devrez peut-etre modifier le port dans compose.override.yaml
) else (
    echo [OK] Le port 8080 est disponible
)
echo.

REM Resultat final
echo ========================================
if %ERROR%==0 (
    echo  ✓ Environnement pret !
    echo ========================================
    echo.
    echo Vous pouvez maintenant lancer l'application avec:
    echo   - Double-clic sur start-dev.bat
    echo   - OU: docker compose up -d
    echo.
    echo Puis acceder a: http://localhost:8080
) else (
    echo  ✗ Des erreurs ont ete detectees
    echo ========================================
    echo.
    echo Veuillez corriger les erreurs ci-dessus avant de continuer.
)
echo.
pause
