# 🚀 Déploiement 3Tek-Europe - Guide Rapide

## 📚 Documentation disponible

| Fichier | Description |
|---------|-------------|
| **DEPLOIEMENT_RESUME.md** | 📋 Résumé complet du déploiement actuel avec checklist |
| **DEPLOIEMENT_CPANEL.md** | 📖 Guide détaillé de déploiement sur cPanel |
| **COMMANDES_RAPIDES.md** | ⚡ Commandes utiles pour maintenance quotidienne |
| **deploy_cpanel.sh** | 🤖 Script automatique de déploiement |

---

## ⚡ Déploiement Rapide (3 étapes)

### 1️⃣ Se connecter au serveur
```bash
ssh votre-user@3tek-europe.com
cd public_html/3tek
```

### 2️⃣ Exécuter le script de déploiement
```bash
chmod +x deploy_cpanel.sh
./deploy_cpanel.sh
```

### 3️⃣ Vérifier
- ✅ Site : https://3tek-europe.com
- ✅ Admin : https://3tek-europe.com/admin
- ✅ Logs : `tail -f var/log/prod.log`

---

## 🆕 Nouveautés de cette version (24/10/2025)

### ✅ Corrections
- **EmailLogCrudController** : Suppression des actions dupliquées (DELETE, batchDelete)

### ✨ Nouvelles fonctionnalités
1. **Système de logs emails**
   - Enregistrement automatique de tous les emails
   - Interface admin : `/admin` → "Logs Emails"
   - Action "Supprimer logs > 30 jours"

2. **Pages RGPD**
   - Politique de confidentialité : `/rgpd/privacy-policy`
   - Mentions légales : `/rgpd/legal-notice`
   - Mes données : `/rgpd/my-data`

3. **Timeout de session**
   - Déconnexion automatique après 30 minutes
   - Message flash informatif

4. **Configuration timezone**
   - Europe/Paris (GMT+2)
   - Dates correctes dans toute l'application

---

## ⚠️ Important après déploiement

### Migration base de données (OBLIGATOIRE)
```bash
php bin/console doctrine:migrations:migrate --no-interaction
```
Cette commande crée la table `email_log` nécessaire au système de logs.

### Vérifications post-déploiement
```bash
# 1. Vérifier le schéma de base de données
php bin/console doctrine:schema:validate

# 2. Tester les logs emails
# Aller sur /admin → Logs Emails

# 3. Vérifier les pages RGPD
curl -I https://3tek-europe.com/rgpd/privacy-policy

# 4. Tester le timeout (attendre 30 min ou modifier la config)
```

---

## 🔧 Configuration requise

### Fichier `.env` (à vérifier)
```env
APP_ENV=prod
APP_SECRET=VOTRE_CLE_SECRETE
APP_DEBUG=0

DATABASE_URL="mysql://user:pass@localhost:3306/3tek_prod"
MAILER_DSN=smtp://noreply@3tek-europe.com:PASSWORD@mail.3tek-europe.com:587

APP_URL=https://3tek-europe.com
```

### Permissions
```bash
chmod -R 755 var/
chmod -R 755 public/uploads/
chmod 644 .env
```

---

## 🐛 Problèmes courants

### Erreur 500
```bash
# Vider le cache
rm -rf var/cache/prod/*
php bin/console cache:clear --env=prod
php bin/console cache:warmup --env=prod
```

### Migration échoue
```bash
# Vérifier la connexion DB
php bin/console doctrine:query:sql "SELECT 1"

# Voir le statut des migrations
php bin/console doctrine:migrations:status
```

### Emails non envoyés
```bash
# Tester la config SMTP
php bin/console mailer:test noreply@3tek-europe.com

# Vérifier les logs
tail -f var/log/prod.log | grep -i "mail"
```

---

## 📊 Checklist complète

### Avant déploiement
- [ ] Code testé en local
- [ ] Migrations créées et testées
- [ ] `.env.example` à jour
- [ ] Documentation à jour
- [ ] Commit et push sur GitHub

### Pendant déploiement
- [ ] Pull des modifications
- [ ] Installation des dépendances
- [ ] Exécution des migrations
- [ ] Cache vidé et réchauffé
- [ ] Permissions correctes

### Après déploiement
- [ ] Site accessible
- [ ] Admin fonctionnel
- [ ] Logs emails visibles
- [ ] Pages RGPD accessibles
- [ ] Emails envoyés correctement
- [ ] Timeout session actif
- [ ] Pas d'erreurs dans les logs

---

## 📞 Support

### En cas de problème
1. **Vérifier les logs** : `tail -f var/log/prod.log`
2. **Consulter la documentation** : Voir fichiers `.md`
3. **Contacter le support** :
   - 📧 contact@3tek-europe.com
   - 📱 +33 1 83 61 18 36

### Commandes de diagnostic
```bash
# Tout vérifier
php bin/console about
php bin/console doctrine:schema:validate
tail -n 50 var/log/prod.log
```

---

## 🔄 Rollback (en cas de problème)

### Revenir à la version précédente
```bash
# Voir les commits
git log --oneline -5

# Revenir au commit précédent
git checkout 7c28efd  # Remplacer par le bon hash

# Ou annuler le dernier commit
git revert HEAD
git push origin main
```

### Restaurer la base de données
```bash
# Si vous avez un backup
mysql -u user_3tek -p 3tek_prod < backup_avant_deploiement.sql
```

---

## 📈 Prochaines étapes

1. **Personnaliser les pages RGPD** selon vos besoins légaux
2. **Configurer les backups automatiques**
3. **Mettre en place un monitoring** (uptime, emails)
4. **Optimiser les performances** si nécessaire
5. **Former les utilisateurs** aux nouvelles fonctionnalités

---

## 🎯 Liens utiles

- **Site** : https://3tek-europe.com
- **Admin** : https://3tek-europe.com/admin
- **GitHub** : https://github.com/odoswitch/3tek
- **Documentation Symfony** : https://symfony.com/doc

---

**Version** : 24/10/2025  
**Commit** : `aa85651`  
**Branche** : `main`

---

> 💡 **Astuce** : Gardez ce fichier ouvert pendant le déploiement pour suivre les étapes !
