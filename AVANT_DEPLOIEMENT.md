# ✅ Checklist avant déploiement - 3Tek-Europe

## 🎯 Statut actuel

✅ **Mode production activé localement pour tests**

```env
APP_ENV=prod
APP_DEBUG=0
```

## 📋 Tests à effectuer MAINTENANT

### 1. Tests fonctionnels ⚙️

Tester toutes les fonctionnalités en mode production :

- [ ] **Page d'accueil** : http://localhost:8080
- [ ] **Connexion utilisateur** : http://localhost:8080/login
- [ ] **Inscription** : http://localhost:8080/register
- [ ] **Dashboard client** : http://localhost:8080/dash
- [ ] **Liste des lots** : Affichage correct
- [ ] **Détail d'un lot** : Images et informations
- [ ] **Ajout au panier** : Fonctionnel
- [ ] **Passage de commande** : Processus complet
- [ ] **Mes commandes** : Affichage de l'historique
- [ ] **Mon profil** : Modification des informations
- [ ] **Administration** : http://localhost:8080/admin
- [ ] **Gestion des lots (admin)** : CRUD complet
- [ ] **Gestion des commandes (admin)** : Validation
- [ ] **Upload d'images** : Fonctionnel

### 2. Tests des pages d'erreur 🚨

- [ ] **Page 404** : http://localhost:8080/page-inexistante
  - Devrait afficher une page personnalisée avec logo 3Tek
  - Bouton "Retour à l'accueil"
  - Informations de contact

- [ ] **Page 500** : Créer une erreur temporaire
  - Vérifier que l'erreur est générique (pas de détails techniques)

### 3. Tests des emails 📧

- [ ] **Email de confirmation de commande** : Envoyé au client
- [ ] **Email de notification admin** : Envoyé aux admins
- [ ] **Email nouveau lot** : Envoyé aux utilisateurs concernés
- [ ] **Liens dans les emails** : Fonctionnels et dynamiques

### 4. Tests de performance ⚡

- [ ] **Temps de chargement** : < 2 secondes
- [ ] **Cache** : Fonctionne correctement
- [ ] **Images** : Chargement optimisé
- [ ] **Base de données** : Requêtes rapides

### 5. Tests de sécurité 🔒

- [ ] **Debug toolbar** : NON visible
- [ ] **Messages d'erreur** : Génériques (pas de stack trace)
- [ ] **Accès admin** : Protégé par authentification
- [ ] **CSRF** : Protection active
- [ ] **Fichiers sensibles** : Non accessibles

## 📝 Fichiers créés pour le déploiement

### Documentation
- ✅ `DEPLOIEMENT_CPANEL.md` - Guide complet de déploiement
- ✅ `README_PRODUCTION.md` - Guide de tests en production
- ✅ `VARIABLES_ENV.md` - Documentation des variables
- ✅ `AVANT_DEPLOIEMENT.md` - Cette checklist

### Configuration
- ✅ `.env.example` - Template pour cPanel
- ✅ `.gitignore` - Mis à jour pour exclure les fichiers sensibles
- ✅ `.env` - Modifié en mode production (à remettre en dev après tests)

### Scripts
- ✅ `test_production.sh` - Script de tests automatisés

## 🔄 Après les tests - AVANT de commit

### 1. Revenir en mode développement

**IMPORTANT** : Ne pas commiter avec `APP_ENV=prod` !

Modifier `.env` :
```env
APP_ENV=dev
APP_DEBUG=1
```

Puis vider le cache :
```bash
docker compose exec php php bin/console cache:clear
```

### 2. Vérifier les fichiers à commiter

```bash
git status
```

**À commiter :**
- ✅ `.env.example`
- ✅ `.gitignore`
- ✅ `DEPLOIEMENT_CPANEL.md`
- ✅ `README_PRODUCTION.md`
- ✅ `VARIABLES_ENV.md`
- ✅ `AVANT_DEPLOIEMENT.md`
- ✅ `test_production.sh`
- ✅ Templates d'erreur personnalisés
- ✅ Tous les fichiers de code modifiés

**À NE PAS commiter :**
- ❌ `.env` (avec les vraies credentials)
- ❌ `/var/`
- ❌ `/vendor/`
- ❌ `/public/uploads/*` (sauf .gitkeep)

### 3. Commit et push

```bash
git add .
git commit -m "Préparation pour déploiement production - Configuration cPanel"
git push origin main
```

## 🚀 Déploiement sur cPanel

Une fois les tests terminés et le code poussé sur Git :

### 1. Sur le serveur cPanel

```bash
# Cloner le repository
git clone https://github.com/votre-repo/3tek.git

# Copier et configurer .env
cp .env.example .env
nano .env
```

### 2. Configuration .env sur cPanel

```env
APP_ENV=prod
APP_SECRET=GENERER_UNE_NOUVELLE_CLE
APP_DEBUG=0
DATABASE_URL="mysql://user_cpanel:password@localhost:3306/base_prod?serverVersion=8.0&charset=utf8mb4"
MAILER_DSN=smtp://noreply@3tek-europe.com:password@mail.3tek-europe.com:587
APP_URL=https://3tek-europe.com
```

### 3. Installation

```bash
# Installer les dépendances
composer install --no-dev --optimize-autoloader

# Créer les tables
php bin/console doctrine:migrations:migrate --no-interaction

# Générer le cache
php bin/console cache:clear --env=prod
php bin/console cache:warmup --env=prod

# Permissions
chmod -R 755 var/
chmod -R 755 public/uploads/
```

### 4. Vérification

- [ ] Site accessible : https://votre-domaine.com
- [ ] Connexion fonctionne
- [ ] Base de données OK
- [ ] Emails envoyés
- [ ] Pas d'erreurs dans les logs

## 📊 Résumé des modifications

### Sécurité
- ✅ Tous les liens localhost remplacés par des URLs dynamiques
- ✅ Pages d'erreur personnalisées (404, 500)
- ✅ Mode production configuré
- ✅ Debug désactivé

### Interface Admin
- ✅ Thème personnalisé avec couleurs 3Tek
- ✅ Responsive sur tous les écrans
- ✅ Icônes agrandies et lisibles
- ✅ Menu latéral optimisé

### Emails
- ✅ Tous les liens dynamiques
- ✅ Compatible avec tout hébergement
- ✅ Templates responsive

### Documentation
- ✅ Guide de déploiement complet
- ✅ Documentation des variables
- ✅ Scripts de test
- ✅ Checklists

## ⚠️ Points d'attention

### Avant le déploiement
1. **Backup** : Faire un backup de la base de données actuelle
2. **Tests** : Tous les tests doivent passer
3. **Mode dev** : Remettre en mode dev avant de commit
4. **Credentials** : Ne jamais commiter les vraies credentials

### Pendant le déploiement
1. **Maintenance** : Activer le mode maintenance si nécessaire
2. **Migrations** : Exécuter les migrations de base de données
3. **Cache** : Vider et régénérer le cache
4. **Permissions** : Vérifier les permissions des dossiers

### Après le déploiement
1. **Tests** : Tester toutes les fonctionnalités en production
2. **Logs** : Surveiller les logs pour détecter les erreurs
3. **Performance** : Vérifier les temps de chargement
4. **Emails** : Tester l'envoi d'emails

## 📞 Support

En cas de problème :
- 📧 Email : contact@3tek-europe.com
- 📞 Téléphone : +33 1 83 61 18 36
- 📖 Documentation : Consulter `DEPLOIEMENT_CPANEL.md`

## 🎉 Prêt pour le déploiement !

Une fois tous les tests passés et le code committé, vous êtes prêt à déployer sur cPanel.

**Bonne chance ! 🚀**

---

**Date de préparation :** {{ "now"|date("d/m/Y H:i") }}
