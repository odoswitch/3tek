@echo off
echo === SCRIPT DE DEPLOIEMENT AUTOMATISE 3TEK-EUROPE ===

echo [INFO] Verification de l'etat Git...
git status

echo [INFO] Ajout de tous les fichiers modifies...
git add -A

echo [INFO] Creation du commit...
git commit -m "feat: Deploiement production - Configuration SMTP, corrections admin et optimisations

🚀 DEPLOIEMENT PRODUCTION CPANEL

✅ Configuration SMTP:
- Identifiants odoip.net configures
- SSL/TLS sur port 465
- Authentification securisee

✅ Corrections Admin:
- Permissions cache Symfony corrigees
- Services publics en mode production
- Scripts d'initialisation ameliores

✅ Fonctionnalites:
- Interface admin entierement fonctionnelle
- Systeme de commandes et file d'attente
- Generation PDF des commandes
- Notifications email automatiques

✅ Optimisations:
- Cache Symfony optimise
- Scripts de maintenance automatique
- Documentation complete deploiement
- Tests de validation integres

📋 Pret pour deploiement cPanel avec base de donnees mise a jour"

echo [INFO] Push vers le repository distant...
git push origin main

if %errorlevel% equ 0 (
    echo [INFO] ✅ Push reussi !
    echo [INFO] Repository mis a jour avec succes
) else (
    echo [ERROR] ❌ Erreur lors du push
    pause
    exit /b 1
)

echo.
echo [INFO] === INFORMATIONS DE DEPLOIEMENT ===
echo.
echo 📋 Etapes suivantes pour cPanel:
echo 1. Se connecter a cPanel
echo 2. Aller dans le repertoire de l'application
echo 3. Executer: git pull origin main
echo 4. Configurer les variables d'environnement
echo 5. Executer les migrations de base de donnees
echo 6. Tester la configuration SMTP
echo.
echo 📧 Configuration SMTP a utiliser:
echo MAILER_DSN=smtp://noreply%%40odoip.net:Ngamba%%2D123@mail.odoip.net:465?encryption=ssl
echo.
echo 📖 Documentation complete:
echo - PROCEDURE_DEPLOIEMENT_CPANEL_COMPLETE.md
echo - CONFIGURATION_SMTP_ODOIP.md
echo.
echo [INFO] ✅ Script de deploiement termine !
pause

