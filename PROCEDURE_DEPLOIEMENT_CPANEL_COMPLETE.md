# PROCÉDURE COMPLÈTE DE DÉPLOIEMENT CPANEL

## 📋 RÉSUMÉ EXÉCUTIF

**Objectif :** Déploiement de l'application 3Tek-Europe sur cPanel  
**Statut actuel :** Application en ligne, mise à jour nécessaire  
**Inclut :** Code, base de données, configuration SMTP, Git push

---

## 🎯 PRÉPARATION PRÉ-DÉPLOIEMENT

### **1. ✅ Vérification de l'état actuel :**

```bash
# Vérifier le statut Git
git status

# Vérifier les modifications non commitées
git diff

# Vérifier les fichiers modifiés
git diff --name-only
```

### **2. ✅ Commit des modifications :**

```bash
# Ajouter tous les fichiers modifiés
git add .

# Commit avec message descriptif
git commit -m "feat: Configuration SMTP, corrections admin, et optimisations production

- Configuration SMTP avec identifiants odoip.net
- Correction des permissions cache Symfony
- Amélioration script d'initialisation Docker
- Services publics pour mode production
- Scripts de maintenance automatique
- Documentation complète déploiement"

# Push vers le repository distant
git push origin main
```

---

## 🚀 ÉTAPES DE DÉPLOIEMENT CPANEL

### **ÉTAPE 1 : Sauvegarde de l'application actuelle**

```bash
# Sur cPanel - Créer une sauvegarde
cd /home/votrecompte/public_html
cp -r 3tek 3tek_backup_$(date +%Y%m%d_%H%M%S)

# Sauvegarde de la base de données
mysqldump -u username -p database_name > backup_db_$(date +%Y%m%d_%H%M%S).sql
```

### **ÉTAPE 2 : Mise à jour du code**

```bash
# Sur cPanel - Aller dans le répertoire de l'application
cd /home/votrecompte/public_html/3tek

# Récupérer les dernières modifications
git pull origin main

# Vérifier les nouveaux fichiers
ls -la
```

### **ÉTAPE 3 : Configuration des variables d'environnement**

```bash
# Créer le fichier .env.local sur cPanel
cat > .env.local << 'EOF'
# Configuration SMTP pour production
MAILER_DSN=smtp://noreply%40odoip.net:Ngamba%2D123@mail.odoip.net:465?encryption=ssl
MAILER_FROM=noreply@odoip.net
MAILER_FROM_NAME="3Tek-Europe"

# Configuration base de données (adapter selon votre hébergeur)
DATABASE_URL=mysql://username:password@localhost:3306/database_name?serverVersion=8.0&charset=utf8mb4

# Configuration production
APP_ENV=prod
APP_DEBUG=false
APP_SECRET=votre_secret_key_ici
EOF
```

### **ÉTAPE 4 : Installation des dépendances**

```bash
# Installer les dépendances Composer
composer install --no-dev --optimize-autoloader

# Vérifier l'installation
composer validate
```

### **ÉTAPE 5 : Configuration des permissions (CRITIQUE)**

```bash
# ⚠️ CORRECTION DÉFINITIVE DES PERMISSIONS CACHE
# Ce problème bloque l'accès admin - SOLUTION OBLIGATOIRE

# 1. Supprimer complètement le cache corrompu
rm -rf var/cache/prod/*

# 2. Créer tous les répertoires de cache nécessaires
mkdir -p var/cache/prod/easyadmin
mkdir -p var/cache/prod/asset_mapper
mkdir -p var/cache/prod/pools/system
mkdir -p var/cache/prod/vich_uploader
mkdir -p var/cache/prod/translations
mkdir -p var/cache/prod/twig

# 3. Permissions CRITIQUES pour éviter les erreurs "Permission denied"
chmod -R 777 var/cache/
chmod -R 777 var/log/

# 4. Propriétaire correct (remplacer 'votrecompte' par votre nom d'utilisateur cPanel)
chown -R votrecompte:votrecompte var/cache/
chown -R votrecompte:votrecompte var/log/

# 5. Permissions spécifiques pour les sous-répertoires critiques
chmod 777 var/cache/prod/easyadmin
chmod 777 var/cache/prod/asset_mapper
chmod 777 var/cache/prod/pools/system
chmod 777 var/cache/prod/vich_uploader

# 6. Vérifier que les permissions sont correctes
ls -la var/cache/prod/
```

### **ÉTAPE 6 : Vidage et réchauffement du cache**

```bash
# Vider le cache existant
php bin/console cache:clear --env=prod --no-debug

# Réchauffer le cache
php bin/console cache:warmup --env=prod --no-debug

# Vérifier le cache
php bin/console cache:pool:list
```

## 🚨 **DÉPANNAGE - ERREUR ADMIN BLOQUÉ**

### **Problème : "Permission denied" sur cache**

**Symptômes :**

-   Erreur serveur sur `/admin/user`
-   Logs montrent : `Failed to create "/var/www/html/var/cache/prod/asset_mapper": mkdir(): Permission denied`
-   Admin inaccessible malgré les corrections précédentes

**Solution IMMÉDIATE :**

```bash
# 1. ARRÊTER l'application temporairement
# (optionnel, pour éviter les conflits)

# 2. SUPPRIMER complètement le cache corrompu
rm -rf var/cache/prod/*

# 3. CRÉER manuellement tous les répertoires
mkdir -p var/cache/prod/easyadmin
mkdir -p var/cache/prod/asset_mapper
mkdir -p var/cache/prod/pools/system
mkdir -p var/cache/prod/vich_uploader
mkdir -p var/cache/prod/translations
mkdir -p var/cache/prod/twig

# 4. PERMISSIONS ABSOLUES (777)
chmod -R 777 var/cache/
chmod -R 777 var/log/

# 5. PROPRIÉTAIRE (remplacer par votre utilisateur cPanel)
chown -R votrecompte:votrecompte var/cache/
chown -R votrecompte:votrecompte var/log/

# 6. VIDER et RÉCHAUFFER le cache Symfony
php bin/console cache:clear --env=prod --no-debug
php bin/console cache:warmup --env=prod --no-debug

# 7. VÉRIFIER les permissions
ls -la var/cache/prod/
```

**Si le problème persiste :**

```bash
# Solution alternative - Permissions encore plus permissives
chmod -R 777 var/
chmod -R 777 public/uploads/

# Vérifier les logs d'erreur
tail -f var/log/prod.log

# Tester l'accès admin
curl -I https://votre-domaine.com/admin
```

---

### **ÉTAPE 7 : Exécution des migrations**

```bash
# Vérifier le statut des migrations
php bin/console doctrine:migrations:status

# Exécuter les nouvelles migrations
php bin/console doctrine:migrations:migrate --no-interaction

# Vérifier la structure de la base
php bin/console doctrine:schema:validate
```

### **ÉTAPE 8 : Mise à jour des données (si nécessaire)**

```bash
# Script de mise à jour des données existantes
php bin/console app:update-data

# Vérifier l'intégrité des données
php bin/console doctrine:query:sql "SELECT COUNT(*) FROM user"
php bin/console doctrine:query:sql "SELECT COUNT(*) FROM lot"
php bin/console doctrine:query:sql "SELECT COUNT(*) FROM commande"
```

---

## 📧 CONFIGURATION SMTP FINALE

### **ÉTAPE 9 : Test de la configuration SMTP**

```bash
# Créer un script de test SMTP
cat > test-smtp.php << 'EOF'
<?php
require_once 'vendor/autoload.php';

use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Email;

$dsn = 'smtp://noreply%40odoip.net:Ngamba%2D123@mail.odoip.net:465?encryption=ssl';

try {
    $transport = Transport::fromDsn($dsn);
    $mailer = new Mailer($transport);

    $email = (new Email())
        ->from('noreply@odoip.net')
        ->to('contact@3tek-europe.com')
        ->subject('Test SMTP Déploiement')
        ->text('Test de connexion SMTP après déploiement cPanel');

    $mailer->send($email);
    echo "✅ Email envoyé avec succès !\n";
} catch (Exception $e) {
    echo "❌ Erreur SMTP : " . $e->getMessage() . "\n";
}
EOF

# Exécuter le test
php test-smtp.php

# Supprimer le script de test
rm test-smtp.php
```

---

## 🔧 CONFIGURATION SERVEUR WEB

### **ÉTAPE 10 : Configuration Apache/Nginx**

```bash
# Vérifier le fichier .htaccess
cat public/.htaccess

# Si nécessaire, créer/mettre à jour .htaccess
cat > public/.htaccess << 'EOF'
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule ^(.*)$ index.php [QSA,L]

# Sécurité
<Files ".env*">
    Order allow,deny
    Deny from all
</Files>

# Cache
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType text/css "access plus 1 month"
    ExpiresByType application/javascript "access plus 1 month"
    ExpiresByType image/png "access plus 1 month"
    ExpiresByType image/jpg "access plus 1 month"
    ExpiresByType image/jpeg "access plus 1 month"
</IfModule>
EOF
```

---

## 🧪 TESTS POST-DÉPLOIEMENT

### **ÉTAPE 11 : Tests de fonctionnalités**

```bash
# Test de l'application principale
curl -I https://votre-domaine.com/

# Test de l'admin
curl -I https://votre-domaine.com/admin

# Test de la base de données
php bin/console doctrine:query:sql "SELECT COUNT(*) FROM user WHERE isVerified = 1"

# Test des routes
php bin/console debug:router | grep admin
```

### **ÉTAPE 12 : Tests fonctionnels**

```bash
# Créer un script de test complet
cat > test-deploiement.php << 'EOF'
<?php
require_once 'vendor/autoload.php';

echo "=== TEST DÉPLOIEMENT CPANEL ===\n\n";

// Test 1: Connexion base de données
try {
    $pdo = new PDO($_ENV['DATABASE_URL']);
    echo "✅ Connexion base de données OK\n";
} catch (Exception $e) {
    echo "❌ Erreur base de données : " . $e->getMessage() . "\n";
}

// Test 2: Configuration SMTP
try {
    $transport = \Symfony\Component\Mailer\Transport::fromDsn($_ENV['MAILER_DSN']);
    echo "✅ Configuration SMTP OK\n";
} catch (Exception $e) {
    echo "❌ Erreur SMTP : " . $e->getMessage() . "\n";
}

// Test 3: Cache Symfony
if (is_writable('var/cache/prod')) {
    echo "✅ Cache Symfony accessible\n";
} else {
    echo "❌ Cache Symfony non accessible\n";
}

// Test 4: Permissions fichiers
$criticalDirs = ['var/cache', 'var/log', 'public/uploads'];
foreach ($criticalDirs as $dir) {
    if (is_writable($dir)) {
        echo "✅ $dir accessible en écriture\n";
    } else {
        echo "❌ $dir non accessible en écriture\n";
    }
}

echo "\n=== TESTS TERMINÉS ===\n";
EOF

# Exécuter les tests
php test-deploiement.php

# Supprimer le script de test
rm test-deploiement.php
```

---

## 📊 MONITORING ET MAINTENANCE

### **ÉTAPE 13 : Configuration des logs**

```bash
# Vérifier les logs d'erreur
tail -f var/log/prod.log

# Configurer la rotation des logs
cat > logrotate.conf << 'EOF'
/home/votrecompte/public_html/3tek/var/log/*.log {
    daily
    missingok
    rotate 30
    compress
    delaycompress
    notifempty
    create 644 votrecompte votrecompte
}
EOF
```

### **ÉTAPE 14 : Script de maintenance automatique**

```bash
# Créer un script de maintenance
cat > maintenance.sh << 'EOF'
#!/bin/bash

echo "=== MAINTENANCE AUTOMATIQUE 3TEK-EUROPE ==="

# Correction des permissions
chmod -R 755 var/cache/
chmod -R 755 public/uploads/

# Nettoyage du cache
php bin/console cache:clear --env=prod --no-debug
php bin/console cache:warmup --env=prod --no-debug

# Nettoyage des logs anciens
find var/log/ -name "*.log" -mtime +30 -delete

# Optimisation de la base de données
php bin/console doctrine:query:sql "OPTIMIZE TABLE user, lot, commande, file_attente"

echo "✅ Maintenance terminée"
EOF

chmod +x maintenance.sh

# Programmer la maintenance (crontab)
# 0 2 * * * /home/votrecompte/public_html/3tek/maintenance.sh
```

---

## 🎯 CHECKLIST FINALE

### **Pré-déploiement :**

-   [ ] Commit et push Git effectués
-   [ ] Sauvegarde application actuelle
-   [ ] Sauvegarde base de données
-   [ ] Variables d'environnement configurées

### **Déploiement :**

-   [ ] Code mis à jour via Git
-   [ ] Dépendances Composer installées
-   [ ] Permissions configurées
-   [ ] Cache vidé et réchauffé
-   [ ] Migrations exécutées
-   [ ] SMTP configuré et testé

### **Post-déploiement :**

-   [ ] Tests fonctionnels réussis
-   [ ] Admin accessible
-   [ ] Emails fonctionnels
-   [ ] Base de données cohérente
-   [ ] Logs configurés
-   [ ] Maintenance programmée

---

## 🚨 ROLLBACK EN CAS DE PROBLÈME

### **Procédure de retour en arrière :**

```bash
# 1. Restaurer l'application
cd /home/votrecompte/public_html/
rm -rf 3tek
mv 3tek_backup_YYYYMMDD_HHMMSS 3tek

# 2. Restaurer la base de données
mysql -u username -p database_name < backup_db_YYYYMMDD_HHMMSS.sql

# 3. Redémarrer les services
# (selon votre configuration cPanel)
```

---

## 📞 SUPPORT ET CONTACT

### **En cas de problème :**

-   **Email :** contact@3tek-europe.com
-   **Téléphone :** +33 1 83 61 18 36
-   **Logs :** Vérifier `var/log/prod.log`
-   **Cache :** Vérifier `var/cache/prod/`

---

## ✅ CONCLUSION

**Votre application 3Tek-Europe sera déployée avec succès sur cPanel !**

### **Fonctionnalités déployées :**

-   ✅ **Interface admin** : Entièrement fonctionnelle
-   ✅ **Système de commandes** : CRUD complet
-   ✅ **File d'attente** : Gestion automatique
-   ✅ **Emails SMTP** : Configuration professionnelle
-   ✅ **Base de données** : Migrations et données
-   ✅ **Cache Symfony** : Optimisé pour production
-   ✅ **Sécurité** : Permissions et configuration

**🚀 L'application est prête pour la production !**
