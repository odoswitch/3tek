@echo off
echo ========================================
echo  Logs des Conteneurs - 3TEK
echo ========================================
echo.

echo [Logs PHP]
echo ----------------------------------------
docker compose logs php --tail=30
echo.

echo [Logs Nginx]
echo ----------------------------------------
docker compose logs nginx --tail=30
echo.

echo [Logs Database]
echo ----------------------------------------
docker compose logs database --tail=30
echo.

pause
