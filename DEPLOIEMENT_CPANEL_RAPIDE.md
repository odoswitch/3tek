# 🚀 Déploiement cPanel - Guide Rapide

## ⚡ Procédure en 10 étapes

### 1️⃣ Préparer cPanel
```bash
# Créer la base de données MySQL
Nom: db_3tek
User: [créer un utilisateur]
Password: [mot de passe fort]
```

### 2️⃣ Configurer PHP 8.2
- Sélectionner PHP 8.2+ dans cPanel
- Activer extensions: pdo_mysql, mbstring, gd, zip, intl, bcmath

### 3️⃣ Cloner le projet
```bash
ssh user@serveur.com
cd public_html
git clone https://github.com/odoswitch/3tek.git
cd 3tek
```

### 4️⃣ Installer dépendances
```bash
composer install --no-dev --optimize-autoloader
```

### 5️⃣ Configurer .env
```bash
cp .env.example .env
nano .env
```

**Variables importantes :**
```env
APP_ENV=prod
APP_SECRET=[générer avec: openssl rand -hex 32]

DATABASE_URL="mysql://USER:PASSWORD@localhost:3306/db_3tek?serverVersion=8.0"

# Email ODOIP TELECOM
MAILER_DSN=smtp://ngamba%40congoelectronicenter.com:Ngamba%2D123@mail.congoelectronicenter.com:465?encryption=ssl
MAILER_FROM=ngamba@congoelectronicenter.com
```

### 6️⃣ Créer les tables
```bash
php bin/console doctrine:schema:update --force
# OU
php bin/console doctrine:migrations:migrate --no-interaction
```

### 7️⃣ Optimiser le cache
```bash
php bin/console cache:clear --env=prod
php bin/console cache:warmup --env=prod
php bin/console assets:install public
```

### 8️⃣ Permissions
```bash
chmod -R 755 var/
chmod -R 755 public/uploads/
chmod 644 .env
```

### 9️⃣ Configurer le domaine
- Document Root: `/public_html/3tek/public`
- Vérifier que `.htaccess` existe dans `/public`

### 🔟 Tester
```bash
# Tester l'envoi d'email
php bin/console mailer:test votre-email@example.com

# Vérifier l'application
curl -I https://votre-domaine.com
```

## ✅ Checklist finale

- [ ] Base de données créée et accessible
- [ ] PHP 8.2+ avec toutes les extensions
- [ ] Fichiers uploadés et dépendances installées
- [ ] `.env` configuré avec bonnes valeurs
- [ ] Tables de base de données créées
- [ ] Cache généré et optimisé
- [ ] Permissions correctes (755 pour var/, public/uploads/)
- [ ] Document root pointant vers `/public`
- [ ] Test d'envoi d'email réussi
- [ ] Application accessible via navigateur

## 🆘 Dépannage

### Erreur 500
```bash
# Vérifier les logs
tail -f var/log/prod.log

# Régénérer le cache
php bin/console cache:clear --env=prod --no-warmup
php bin/console cache:warmup --env=prod
```

### Problème de permissions
```bash
chmod -R 755 var/ public/uploads/
chown -R votre-user:votre-user .
```

### Email ne fonctionne pas
- Vérifier l'encodage URL dans MAILER_DSN (@ = %40, - = %2D)
- Tester avec: `php bin/console mailer:test email@test.com`
- Vérifier les logs: `tail -f var/log/prod.log`

## 📞 Support

- Email: contact@3tek-europe.com
- Téléphone: +33 1 83 61 18 36
- Hébergement: ODOIP TELECOM, Datacenter Saint-Denis

---

**Dernière mise à jour:** 25/10/2025  
**Version:** 1.0.0
