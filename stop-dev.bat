@echo off
echo ========================================
echo  Arret de l'environnement 3TEK
echo ========================================
echo.

docker compose -f compose.yaml -f compose.override.yaml down

if errorlevel 1 (
    echo [ERREUR] Echec de l'arret des conteneurs
    pause
    exit /b 1
)

echo.
echo [OK] Environnement arrete avec succes!
echo.
pause
