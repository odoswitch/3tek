@echo off
REM Configuration Command Prompt pour 3tek
REM Ce script configure Command Prompt comme terminal par défaut

echo Configuration Command Prompt pour 3tek...
echo.

REM Vérifier si Docker est disponible
docker --version >nul 2>&1
if %errorlevel% neq 0 (
    echo ERREUR: Docker n'est pas installé ou pas dans le PATH
    echo Veuillez installer Docker Desktop
    pause
    exit /b 1
)

echo Docker detecte avec succes
echo.

REM Afficher les commandes disponibles
echo === COMMANDES DISPONIBLES ===
echo.
echo 1. Demarrer l'application:
echo    docker compose up -d
echo.
echo 2. Arreter l'application:
echo    docker compose down
echo.
echo 3. Voir les logs:
echo    docker compose logs -f
echo.
echo 4. Acceder au conteneur PHP:
echo    docker compose exec php bash
echo.
echo 5. Executer des commandes Symfony:
echo    docker compose exec php php bin/console cache:clear
echo.
echo 6. Voir le statut des conteneurs:
echo    docker compose ps
echo.
echo 7. Redemarrer complet:
echo    docker compose down ^&^& docker compose up -d --build --force-recreate
echo.

REM Vérifier l'état des conteneurs
echo === ETAT ACTUEL ===
docker compose ps

echo.
echo Configuration terminée!
echo Utilisez les commandes ci-dessus pour gerer l'application.
echo.
pause


