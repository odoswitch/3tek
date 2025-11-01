# 📚 INDEX DES DOCUMENTS - Application 3tek

## 📋 Documents Principaux

### **Rapports de Déploiement**
1. **`RAPPORT_DEPLOIEMENT_CPANEL.md`** - Rapport complet pour le déploiement sur cPanel
2. **`INSTALLATION_REPORT.md`** - Rapport d'installation Docker
3. **`RAPPORT_FINAL.md`** - Rapport final complet
4. **`RECAPITULATIF_COMPLET.md`** - Récapitulatif complet
5. **`RECAPITULATIF_FINAL.md`** - Récapitulatif final

### **Configuration et Accès**
6. **`CONFIGURATION_PHPMYADMIN.md`** - Configuration PhpMyAdmin
7. **`SUCCESS_REPORT.md`** - Rapport de succès

## 🔧 Scripts Disponibles

### **Scripts de Déploiement**
- **`scripts/deploy-3tek-cpanel.sh`** - Déploiement complet sur cPanel
- **`scripts/maintenance-3tek.sh`** - Maintenance et sauvegarde
- **`scripts/fix-3tek.php`** - Correction des problèmes courants

### **Scripts de Base de Données**
- **`scripts/backup-db.sh`** - Sauvegarde de la base de données
- **`scripts/restore-db.sh`** - Restauration de la base de données

### **Scripts de Monitoring**
- **`scripts/monitor-3tek.sh`** - Monitoring complet de l'application
- **`scripts/3tek-manage.sh`** - Gestion des conteneurs Docker

## 🌐 Accès Rapide

### **Application 3tek**
- **Développement** : http://45.11.51.2:8084
- **Production** : https://votre-domaine.com

### **PhpMyAdmin**
- **Développement** : http://45.11.51.2:8087
- **Production** : https://votre-domaine.com/phpmyadmin

### **Mailpit (Développement)**
- **Interface** : http://45.11.51.2:8025

## 🔐 Informations de Connexion

### **Base de Données (Développement)**
- **Serveur** : `database`
- **Utilisateur** : `root`
- **Mot de passe** : `ngamba123`
- **Base de données** : `3tek`

### **Base de Données (Production)**
- **Serveur** : `localhost`
- **Utilisateur** : `[Votre utilisateur BDD cPanel]`
- **Mot de passe** : `[Votre mot de passe BDD cPanel]`
- **Base de données** : `[Nom de votre BDD]`

## 🚀 Commandes Utiles

### **Déploiement cPanel**
```bash
./scripts/deploy-3tek-cpanel.sh [domain] [db_user] [db_password] [db_name]
```

### **Maintenance**
```bash
./scripts/maintenance-3tek.sh [backup|restore|update|status]
```

### **Monitoring**
```bash
./scripts/monitor-3tek.sh
```

### **Sauvegarde**
```bash
./scripts/backup-db.sh
```

### **Restauration**
```bash
./scripts/restore-db.sh <backup_file.sql[.gz]>
```

### **Correction**
```bash
php scripts/fix-3tek.php
```

## 📊 Structure de l'Application

### **Répertoires Principaux**
- **`public/`** - Point d'entrée web
- **`src/`** - Code source de l'application
- **`templates/`** - Templates Twig
- **`var/`** - Cache et logs
- **`vendor/`** - Dépendances Composer
- **`scripts/`** - Scripts de déploiement et maintenance

### **Fichiers de Configuration**
- **`.env`** - Variables d'environnement
- **`composer.json`** - Dépendances PHP
- **`composer.lock`** - Versions verrouillées
- **`docker-compose.yml`** - Configuration Docker

## 🗄️ Base de Données

### **Tables Principales**
- `user` - Utilisateurs
- `category` - Catégories
- `type` - Types
- `lot` - Lots/Produits
- `commande` - Commandes
- `favori` - Favoris
- `email_log` - Logs emails
- `file_attente` - Files d'attente

## 🔧 Configuration Requise

### **PHP**
- Version : 8.2+
- Extensions : pdo_mysql, mbstring, gd, zip, intl, curl, json, openssl

### **MySQL**
- Version : 8.0+
- Charset : utf8mb4
- Collation : utf8mb4_unicode_ci

### **Serveur Web**
- Apache ou Nginx
- Mod_rewrite activé
- SSL recommandé

## 🆘 Support et Dépannage

### **Problèmes Courants**
1. **Erreur 500** - Vérifier permissions et cache
2. **Erreur BDD** - Vérifier connexion
3. **Cache corrompu** - Vider le cache
4. **Permissions** - Corriger les permissions

### **Commandes de Dépannage**
```bash
# Vider le cache
php bin/console cache:clear --env=prod

# Vérifier la BDD
php bin/console doctrine:query:sql "SELECT 1" --env=prod

# Corriger les permissions
chmod -R 755 var/
chmod -R 755 public/

# Monitoring complet
./scripts/monitor-3tek.sh
```

## 📞 Contact

**3tek Europe**
- **Email** : contact@3tek-europe.com
- **Téléphone** : +33 1 83 61 18 36
- **Site web** : https://3tek-europe.com

---

**Index généré le : 28 octobre 2025**  
**Application : 3tek Symfony 7.3**  
**Serveur : 45.11.51.2**  
**Statut : ✅ OPÉRATIONNEL**
