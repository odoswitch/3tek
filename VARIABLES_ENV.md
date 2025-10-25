# 📋 Variables d'environnement - 3Tek-Europe

## 🔐 Variables obligatoires

### APP_ENV
**Description :** Environnement de l'application  
**Valeurs possibles :** `dev`, `prod`, `test`  
**Production :** `prod`  
**Exemple :**
```env
APP_ENV=prod
```

### APP_SECRET
**Description :** Clé secrète pour la sécurité (sessions, CSRF, etc.)  
**Format :** Chaîne aléatoire de 32 caractères minimum  
**⚠️ IMPORTANT :** Doit être unique et différente entre dev et prod  
**Générer une nouvelle clé :**
```bash
php -r "echo bin2hex(random_bytes(16));"
```
**Exemple :**
```env
APP_SECRET=a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6
```

### APP_DEBUG
**Description :** Active/désactive le mode debug  
**Valeurs :** `0` (désactivé) ou `1` (activé)  
**Production :** `0`  
**Développement :** `1`  
**Exemple :**
```env
APP_DEBUG=0
```

### DATABASE_URL
**Description :** URL de connexion à la base de données  
**Format :** `mysql://utilisateur:motdepasse@hote:port/nom_base?serverVersion=X.X&charset=utf8mb4`  

**Développement (Docker) :**
```env
DATABASE_URL="mysql://root:ngamba123@database:3306/3tek?serverVersion=8.0&charset=utf8mb4"
```

**Production (cPanel) :**
```env
DATABASE_URL="mysql://user_3tek:MOT_DE_PASSE@localhost:3306/3tek_prod?serverVersion=8.0&charset=utf8mb4"
```

**Composants :**
- `utilisateur` : Nom d'utilisateur MySQL
- `motdepasse` : Mot de passe MySQL
- `hote` : Adresse du serveur (`localhost` sur cPanel)
- `port` : Port MySQL (généralement `3306`)
- `nom_base` : Nom de la base de données
- `serverVersion` : Version de MySQL (8.0, 5.7, etc.)

### MAILER_DSN
**Description :** Configuration du serveur d'envoi d'emails  

**Développement (Mailpit) :**
```env
MAILER_DSN=smtp://mailer:1025
```

**Production - Option 1 (SMTP cPanel) :**
```env
MAILER_DSN=smtp://noreply@3tek-europe.com:MOT_DE_PASSE@mail.3tek-europe.com:587
```

**Production - Option 2 (Gmail) :**
```env
MAILER_DSN=gmail+smtp://votre-email@gmail.com:mot-de-passe-app@default
```

**Production - Option 3 (SendGrid) :**
```env
MAILER_DSN=sendgrid://API_KEY@default
```

**Composants SMTP :**
- `utilisateur` : Adresse email complète
- `motdepasse` : Mot de passe de l'email
- `hote` : Serveur SMTP (ex: `mail.3tek-europe.com`)
- `port` : Port SMTP (587 pour TLS, 465 pour SSL, 25 non sécurisé)

## 🔧 Variables optionnelles

### APP_URL
**Description :** URL complète de l'application (pour les emails)  
**Format :** URL sans slash final  
**Exemple :**
```env
APP_URL=https://3tek-europe.com
```

### MESSENGER_TRANSPORT_DSN
**Description :** Configuration du système de messages asynchrones  
**Valeur par défaut :** `doctrine://default?auto_setup=0`  
**Exemple :**
```env
MESSENGER_TRANSPORT_DSN=doctrine://default?auto_setup=0
```

## 📝 Configuration par environnement

### Développement local (.env)
```env
APP_ENV=dev
APP_SECRET=31a1dc0a4d2977405a7293b0c56c06fa
APP_DEBUG=1
DATABASE_URL="mysql://root:ngamba123@database:3306/3tek?serverVersion=8.0&charset=utf8mb4"
MAILER_DSN=smtp://mailer:1025
```

### Production cPanel (.env sur le serveur)
```env
APP_ENV=prod
APP_SECRET=GENERER_UNE_NOUVELLE_CLE_UNIQUE
APP_DEBUG=0
DATABASE_URL="mysql://user_3tek:PASSWORD@localhost:3306/3tek_prod?serverVersion=8.0&charset=utf8mb4"
MAILER_DSN=smtp://noreply@3tek-europe.com:PASSWORD@mail.3tek-europe.com:587
APP_URL=https://3tek-europe.com
```

## 🔒 Sécurité

### ⚠️ À NE JAMAIS faire :
- ❌ Commiter le fichier `.env` avec les vraies credentials
- ❌ Utiliser la même `APP_SECRET` en dev et prod
- ❌ Activer `APP_DEBUG=1` en production
- ❌ Partager les mots de passe en clair
- ❌ Utiliser des mots de passe faibles

### ✅ Bonnes pratiques :
- ✅ Utiliser `.env.example` comme template
- ✅ Générer une `APP_SECRET` unique pour chaque environnement
- ✅ Utiliser des mots de passe forts (16+ caractères)
- ✅ Stocker les credentials dans un gestionnaire de mots de passe
- ✅ Changer les mots de passe régulièrement
- ✅ Utiliser HTTPS en production

## 🛠️ Commandes utiles

### Générer une clé secrète
```bash
php -r "echo bin2hex(random_bytes(16));"
```

### Vérifier la configuration
```bash
php bin/console about
```

### Tester la connexion base de données
```bash
php bin/console doctrine:query:sql "SELECT 1"
```

### Tester l'envoi d'email
```bash
php bin/console mailer:test votre-email@example.com
```

### Vider le cache après modification
```bash
php bin/console cache:clear --env=prod
```

## 📧 Configuration email détaillée

### cPanel - Créer un compte email
1. Aller dans **Email Accounts**
2. Créer : `noreply@3tek-europe.com`
3. Noter le mot de passe
4. Utiliser le serveur SMTP : `mail.3tek-europe.com`
5. Port : `587` (TLS recommandé)

### Gmail - Mot de passe d'application
1. Activer l'authentification à 2 facteurs
2. Aller dans **Sécurité** > **Mots de passe des applications**
3. Générer un mot de passe pour "Autre (nom personnalisé)"
4. Utiliser ce mot de passe dans `MAILER_DSN`

### SendGrid
1. Créer un compte sur sendgrid.com
2. Générer une clé API
3. Utiliser : `sendgrid://VOTRE_CLE_API@default`

## 🗄️ Configuration base de données cPanel

### Créer la base de données
1. **MySQL® Databases** dans cPanel
2. Créer une base : `3tek_prod`
3. Créer un utilisateur : `user_3tek`
4. Mot de passe fort (16+ caractères)
5. Associer l'utilisateur à la base
6. Donner TOUS les privilèges

### Importer les données
```bash
# Export depuis dev
mysqldump -u root -p 3tek > backup.sql

# Import en prod (via cPanel ou SSH)
mysql -u user_3tek -p 3tek_prod < backup.sql
```

## 🔍 Vérification

### Checklist de configuration
- [ ] `APP_ENV=prod`
- [ ] `APP_DEBUG=0`
- [ ] `APP_SECRET` unique et différent de dev
- [ ] `DATABASE_URL` avec les bonnes credentials
- [ ] `MAILER_DSN` configuré et testé
- [ ] `APP_URL` avec le bon domaine
- [ ] Connexion base de données testée
- [ ] Envoi d'email testé
- [ ] Cache vidé et régénéré

### Commande de vérification complète
```bash
# Vérifier toute la configuration
php bin/console debug:container --env-vars
```

## 📞 Support

En cas de problème de configuration :
- Vérifier les logs : `var/log/prod.log`
- Tester la connexion DB : `php bin/console doctrine:query:sql "SELECT 1"`
- Tester les emails : `php bin/console mailer:test test@example.com`
- Contact : contact@3tek-europe.com

---

**Dernière mise à jour :** Octobre 2025
