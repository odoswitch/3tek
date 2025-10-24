# Guide de déploiement sur cPanel - 3Tek-Europe

## 📋 Prérequis

- Accès cPanel avec PHP 8.2+
- Base de données MySQL 8.0+
- Accès SSH (recommandé) ou FTP
- Composer installé sur le serveur

## 🚀 Étapes de déploiement

### 1. Préparation locale

```bash
# Tester en mode production localement
APP_ENV=prod php bin/console cache:clear
APP_ENV=prod php bin/console cache:warmup

# Vérifier qu'il n'y a pas d'erreurs
docker compose exec php php bin/console about
```

### 2. Configuration cPanel

#### A. Créer la base de données
1. Aller dans **MySQL® Databases**
2. Créer une nouvelle base de données : `3tek_prod`
3. Créer un utilisateur MySQL
4. Associer l'utilisateur à la base avec tous les privilèges

#### B. Configurer le domaine
1. Aller dans **Domains** ou **Addon Domains**
2. Pointer le document root vers : `/public_html/3tek/public`

### 3. Upload des fichiers

#### Option A : Via Git (recommandé)
```bash
# Se connecter en SSH
ssh votre-user@votre-domaine.com

# Cloner le repository
cd public_html
git clone https://github.com/votre-repo/3tek.git
cd 3tek
```

#### Option B : Via FTP
- Uploader tous les fichiers SAUF :
  - `/var/`
  - `/vendor/`
  - `/.env` (sera créé sur le serveur)

### 4. Configuration de l'environnement

```bash
# Copier le fichier d'exemple
cp .env.example .env

# Éditer le fichier .env
nano .env
```

**Configurer les variables :**

```env
APP_ENV=prod
APP_SECRET=GENERER_UNE_CLE_SECRETE_32_CARACTERES
APP_DEBUG=0

DATABASE_URL="mysql://user_3tek:PASSWORD@localhost:3306/3tek_prod?serverVersion=8.0&charset=utf8mb4"

MAILER_DSN=smtp://noreply@3tek-europe.com:PASSWORD@mail.3tek-europe.com:587

APP_URL=https://3tek-europe.com
```

### 5. Installation des dépendances

```bash
# Installer Composer si nécessaire
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php
php -r "unlink('composer-setup.php');"

# Installer les dépendances (sans dev)
php composer.phar install --no-dev --optimize-autoloader

# Ou si composer est global
composer install --no-dev --optimize-autoloader
```

### 6. Configuration de la base de données

```bash
# Créer les tables
php bin/console doctrine:migrations:migrate --no-interaction

# Ou créer le schéma directement
php bin/console doctrine:schema:create
```

### 7. Optimisation pour la production

```bash
# Vider et réchauffer le cache
php bin/console cache:clear --env=prod
php bin/console cache:warmup --env=prod

# Installer les assets
php bin/console assets:install public --symlink --relative
```

### 8. Permissions des fichiers

```bash
# Donner les bonnes permissions
chmod -R 755 var/
chmod -R 755 public/uploads/
chmod 644 .env

# Si nécessaire, ajuster le propriétaire
chown -R votre-user:votre-user .
```

### 9. Configuration du .htaccess

Le fichier `public/.htaccess` devrait contenir :

```apache
DirectoryIndex index.php

<IfModule mod_negotiation.c>
    Options -MultiViews
</IfModule>

<IfModule mod_rewrite.c>
    RewriteEngine On

    RewriteCond %{REQUEST_URI}::$0 ^(/.+)/(.*)::\2$
    RewriteRule .* - [E=BASE:%1]

    RewriteCond %{HTTP:Authorization} .+
    RewriteRule ^ - [E=HTTP_AUTHORIZATION:%0]

    RewriteCond %{ENV:REDIRECT_STATUS} =""
    RewriteRule ^index\.php(?:/(.*)|$) %{ENV:BASE}/$1 [R=301,L]

    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ %{ENV:BASE}/index.php [L]
</IfModule>

<IfModule !mod_rewrite.c>
    <IfModule mod_alias.c>
        RedirectMatch 307 ^/$ /index.php/
    </IfModule>
</IfModule>
```

### 10. Vérification

#### Tester les URLs :
- `https://votre-domaine.com` → Page d'accueil
- `https://votre-domaine.com/login` → Page de connexion
- `https://votre-domaine.com/admin` → Administration

#### Vérifier les logs :
```bash
tail -f var/log/prod.log
```

## 🔧 Configuration email cPanel

### Option 1 : SMTP cPanel
```env
MAILER_DSN=smtp://noreply@3tek-europe.com:PASSWORD@mail.3tek-europe.com:587
```

### Option 2 : Gmail
1. Activer l'authentification à 2 facteurs
2. Générer un mot de passe d'application
3. Utiliser :
```env
MAILER_DSN=gmail+smtp://votre-email@gmail.com:mot-de-passe-app@default
```

## 📝 Checklist de déploiement

- [ ] Base de données créée
- [ ] Fichiers uploadés
- [ ] `.env` configuré
- [ ] Dépendances installées
- [ ] Migrations exécutées
- [ ] Cache généré
- [ ] Permissions correctes
- [ ] Tests de connexion OK
- [ ] Emails fonctionnels
- [ ] Pages d'erreur personnalisées
- [ ] HTTPS activé (SSL)

## 🔒 Sécurité

### SSL/HTTPS
1. Dans cPanel, aller dans **SSL/TLS Status**
2. Activer AutoSSL ou installer Let's Encrypt
3. Forcer HTTPS dans `.htaccess` :

```apache
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

### Fichiers sensibles
Vérifier que ces fichiers ne sont PAS accessibles :
- `/.env`
- `/config/`
- `/var/`
- `/vendor/`

## 🐛 Dépannage

### Erreur 500
```bash
# Vérifier les logs
tail -f var/log/prod.log

# Vérifier les permissions
ls -la var/

# Recréer le cache
rm -rf var/cache/prod
php bin/console cache:warmup --env=prod
```

### Base de données
```bash
# Tester la connexion
php bin/console doctrine:query:sql "SELECT 1"

# Vérifier le schéma
php bin/console doctrine:schema:validate
```

### Emails non envoyés
```bash
# Tester l'envoi
php bin/console swiftmailer:email:send --from=noreply@3tek-europe.com --to=test@example.com --subject=Test --body=Test
```

## 📊 Maintenance

### Mise à jour
```bash
# Pull les dernières modifications
git pull origin main

# Installer les dépendances
composer install --no-dev --optimize-autoloader

# Migrer la base
php bin/console doctrine:migrations:migrate --no-interaction

# Vider le cache
php bin/console cache:clear --env=prod
```

### Backup
```bash
# Backup base de données
mysqldump -u user_3tek -p 3tek_prod > backup_$(date +%Y%m%d).sql

# Backup fichiers
tar -czf backup_files_$(date +%Y%m%d).tar.gz public/uploads/
```

## 📞 Support

En cas de problème :
- Email : contact@3tek-europe.com
- Téléphone : +33 1 83 61 18 36

---

**Dernière mise à jour :** {{ "now"|date("d/m/Y") }}
