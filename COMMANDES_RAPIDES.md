# 🚀 Commandes Rapides - 3Tek-Europe

## 📦 Déploiement

### Déploiement complet (automatique)
```bash
./deploy_cpanel.sh
```

### Déploiement manuel
```bash
git pull origin main
composer install --no-dev --optimize-autoloader
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console cache:clear --env=prod
php bin/console cache:warmup --env=prod
php bin/console assets:install public --symlink --relative
```

---

## 🗄️ Base de données

### Migrations
```bash
# Exécuter les migrations
php bin/console doctrine:migrations:migrate --no-interaction

# Voir le statut
php bin/console doctrine:migrations:status

# Créer une nouvelle migration
php bin/console make:migration

# Valider le schéma
php bin/console doctrine:schema:validate
```

### Requêtes utiles
```bash
# Compter les logs emails
php bin/console doctrine:query:sql "SELECT COUNT(*) FROM email_log"

# Voir les derniers logs
php bin/console doctrine:query:sql "SELECT * FROM email_log ORDER BY sent_at DESC LIMIT 10"

# Supprimer les vieux logs (> 30 jours)
php bin/console doctrine:query:sql "DELETE FROM email_log WHERE sent_at < DATE_SUB(NOW(), INTERVAL 30 DAY)"
```

---

## 🧹 Cache

### Vider le cache
```bash
# Production
php bin/console cache:clear --env=prod
php bin/console cache:warmup --env=prod

# Développement
php bin/console cache:clear
```

### Forcer le nettoyage
```bash
rm -rf var/cache/prod/*
php bin/console cache:clear --env=prod --no-warmup
php bin/console cache:warmup --env=prod
```

---

## 📧 Emails

### Tester l'envoi
```bash
# Test simple
php bin/console mailer:test noreply@3tek-europe.com

# Vérifier la config
php bin/console debug:config framework mailer
```

### Voir les logs emails (via admin)
- URL : https://3tek-europe.com/admin
- Menu : "Logs Emails"
- Filtres disponibles : statut, type, date

---

## 🔍 Debugging

### Voir les logs
```bash
# Logs en temps réel
tail -f var/log/prod.log

# Dernières 100 lignes
tail -n 100 var/log/prod.log

# Rechercher une erreur
grep -i "error" var/log/prod.log
```

### Informations système
```bash
# Version PHP
php -v

# Extensions PHP
php -m

# Configuration Symfony
php bin/console about

# Routes disponibles
php bin/console debug:router

# Services disponibles
php bin/console debug:container
```

---

## 👥 Utilisateurs

### Créer un admin
```bash
php bin/console app:create-admin
```

### Lister les utilisateurs
```bash
php bin/console doctrine:query:sql "SELECT id, email, roles FROM user"
```

### Changer le mot de passe
```bash
php bin/console security:hash-password
# Puis mettre à jour manuellement dans la base
```

---

## 📊 Maintenance

### Backup base de données
```bash
# Backup complet
mysqldump -u user_3tek -p 3tek_prod > backup_$(date +%Y%m%d_%H%M%S).sql

# Backup avec compression
mysqldump -u user_3tek -p 3tek_prod | gzip > backup_$(date +%Y%m%d_%H%M%S).sql.gz
```

### Backup fichiers
```bash
# Backup uploads
tar -czf backup_uploads_$(date +%Y%m%d).tar.gz public/uploads/

# Backup complet (sans vendor et cache)
tar -czf backup_3tek_$(date +%Y%m%d).tar.gz \
  --exclude='var/cache' \
  --exclude='var/log' \
  --exclude='vendor' \
  --exclude='node_modules' \
  .
```

### Restauration
```bash
# Restaurer la base
mysql -u user_3tek -p 3tek_prod < backup_20251024.sql

# Restaurer les fichiers
tar -xzf backup_uploads_20251024.tar.gz
```

---

## 🔒 Sécurité

### Permissions
```bash
# Définir les bonnes permissions
chmod -R 755 var/
chmod -R 755 public/uploads/
chmod 644 .env

# Vérifier le propriétaire
ls -la var/
ls -la public/uploads/
```

### Vérifier les fichiers sensibles
```bash
# Ces fichiers NE DOIVENT PAS être accessibles via web
curl https://3tek-europe.com/.env  # Doit retourner 403 ou 404
curl https://3tek-europe.com/config/  # Doit retourner 403 ou 404
```

---

## 📈 Performance

### Optimiser l'autoloader
```bash
composer dump-autoload --optimize --no-dev
```

### Précharger le cache
```bash
php bin/console cache:warmup --env=prod
```

### Vérifier les performances
```bash
# Temps de réponse
time curl -I https://3tek-europe.com

# Analyser les requêtes lentes
grep "slow" var/log/prod.log
```

---

## 🧪 Tests

### Vérifier l'installation
```bash
# Vérifier Symfony
php bin/console about

# Vérifier la base de données
php bin/console doctrine:schema:validate

# Vérifier les routes
php bin/console debug:router | grep -i "admin"
```

### Tests fonctionnels
```bash
# Test connexion
curl -I https://3tek-europe.com/login

# Test admin (nécessite authentification)
curl -I https://3tek-europe.com/admin

# Test API (si disponible)
curl -I https://3tek-europe.com/api
```

---

## 🔄 Git

### Commandes courantes
```bash
# Voir le statut
git status

# Voir les modifications
git diff

# Ajouter tous les fichiers
git add .

# Commit
git commit -m "Description des modifications"

# Push
git push origin main

# Pull
git pull origin main

# Voir l'historique
git log --oneline -10
```

### Annuler des modifications
```bash
# Annuler les modifications non commitées
git restore fichier.php

# Annuler le dernier commit (garder les modifications)
git reset --soft HEAD~1

# Revenir à un commit précédent
git checkout COMMIT_HASH
```

---

## 📞 Support

### Logs à vérifier en cas de problème
1. `var/log/prod.log` - Logs Symfony
2. Logs Apache/Nginx du serveur
3. Logs MySQL
4. Logs PHP (php_errors.log)

### Commandes de diagnostic
```bash
# Tout vérifier d'un coup
php bin/console about
php bin/console doctrine:schema:validate
tail -n 50 var/log/prod.log
php bin/console debug:router
```

### Contact
- 📧 Email : contact@3tek-europe.com
- 📱 Téléphone : +33 1 83 61 18 36

---

**Dernière mise à jour** : 24/10/2025
