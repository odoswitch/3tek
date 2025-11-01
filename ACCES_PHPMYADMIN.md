# 🔐 ACCÈS PHPMYADMIN - Application 3tek

## 📋 Informations de Connexion

### **Développement (Docker)**
- **URL** : http://45.11.51.2:8087
- **Serveur** : `database`
- **Utilisateur** : `root`
- **Mot de passe** : `ngamba123`
- **Base de données** : `3tek`

### **Production (cPanel)**
- **URL** : https://votre-domaine.com/phpmyadmin
- **Serveur** : `localhost`
- **Utilisateur** : `[Votre utilisateur BDD cPanel]`
- **Mot de passe** : `[Votre mot de passe BDD cPanel]`
- **Base de données** : `[Nom de votre BDD]`

## 🗄️ Structure de la Base de Données

### **Tables Principales**
- **`user`** - Utilisateurs (comptes clients)
- **`category`** - Catégories de produits
- **`type`** - Types de produits
- **`lot`** - Lots/Produits à vendre
- **`commande`** - Commandes clients
- **`favori`** - Favoris des utilisateurs
- **`email_log`** - Logs des emails envoyés
- **`file_attente`** - Files d'attente pour les lots

### **Utilisateur Admin par Défaut**
- **Email** : admin@3tek.com
- **Mot de passe** : admin123
- **Rôle** : ROLE_ADMIN

## 🔧 Requêtes Utiles

### **Vérification de l'État**
```sql
-- Vérifier les tables
SHOW TABLES;

-- Vérifier la taille de la base
SELECT 
    table_schema AS 'Database',
    ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS 'Size (MB)'
FROM information_schema.tables 
WHERE table_schema = '3tek'
GROUP BY table_schema;

-- Vérifier les utilisateurs
SELECT COUNT(*) as total_users FROM user;

-- Vérifier les lots
SELECT COUNT(*) as total_lots FROM lot;

-- Vérifier les commandes
SELECT 
    status,
    COUNT(*) as count
FROM commande 
GROUP BY status;
```

### **Maintenance**
```sql
-- Optimiser les tables
OPTIMIZE TABLE user, lot, commande, category, type;

-- Vérifier les tables
CHECK TABLE user, lot, commande, category, type;

-- Réparer les tables (si nécessaire)
REPAIR TABLE user, lot, commande, category, type;
```

## 🚀 Scripts de Gestion

### **Sauvegarde**
```bash
./scripts/backup-db.sh
```

### **Restauration**
```bash
./scripts/restore-db.sh <backup_file.sql[.gz]>
```

### **Monitoring**
```bash
./scripts/monitor-3tek.sh
```

## 🔐 Sécurité

### **Configuration .htaccess**
```apache
# Protection PhpMyAdmin
<Files "config.inc.php">
    Order allow,deny
    Deny from all
</Files>

# Limitation d'accès par IP (optionnel)
<RequireAll>
    Require ip 192.168.1.0/24
    Require ip 10.0.0.0/8
</RequireAll>
```

## 📊 Monitoring

### **Vérification des Connexions**
```sql
-- Connexions actives
SHOW PROCESSLIST;

-- Statut des tables
SHOW TABLE STATUS FROM 3tek;
```

### **Logs à Surveiller**
- Logs d'accès PhpMyAdmin
- Logs d'erreur MySQL
- Logs de l'application (var/log/prod.log)

## 🆘 Dépannage

### **Problèmes Courants**
1. **Connexion refusée** - Vérifier les paramètres de connexion
2. **Base de données non trouvée** - Vérifier le nom de la base
3. **Permissions insuffisantes** - Vérifier les droits utilisateur
4. **Erreur de charset** - Vérifier la configuration MySQL

### **Commandes de Vérification**
```bash
# Test de connexion
mysql -u root -pngamba123 -e "SELECT 1;"

# Vérifier les bases de données
mysql -u root -pngamba123 -e "SHOW DATABASES;"

# Vérifier les utilisateurs
mysql -u root -pngamba123 -e "SELECT User, Host FROM mysql.user;"
```

## 📞 Support

**3tek Europe**
- **Email** : contact@3tek-europe.com
- **Téléphone** : +33 1 83 61 18 36
- **Site web** : https://3tek-europe.com

---

**Document généré le : 28 octobre 2025**  
**Application : 3tek Symfony 7.3**  
**Serveur : 45.11.51.2**  
**Statut : ✅ OPÉRATIONNEL**
