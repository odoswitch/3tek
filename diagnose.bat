@echo off
echo ========================================
echo  Diagnostic Docker - 3TEK
echo ========================================
echo.

echo [1] Etat des conteneurs:
docker compose ps
echo.

echo [2] Logs des conteneurs:
docker compose logs --tail=50
echo.

echo [3] Conteneurs en cours d'execution:
docker ps -a
echo.

pause
