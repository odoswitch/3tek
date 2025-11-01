# Application 3tek - Symfony 7.3

## 📋 Description
Application web développée avec Symfony 7.3, déployable avec Docker.

## 🚀 Installation et Démarrage

### 1. Installation complète
```bash
cd /opt/docker/3tek
chmod +x scripts/3tek-manage.sh
./scripts/3tek-manage.sh install
```

### 2. Commandes de gestion
```bash
# Démarrer l'application
./scripts/3tek-manage.sh start

# Arrêter l'application
./scripts/3tek-manage.sh stop

# Redémarrer l'application
./scripts/3tek-manage.sh restart

# Voir le statut
./scripts/3tek-manage.sh status

# Voir les logs
./scripts/3tek-manage.sh logs
```

## 🌐 Accès

### Interface Web
- **URL**: http://45.11.51.2:8085
- **Utilisateur**: admin
- **Mot de passe**: admin123

### PhpMyAdmin
- **URL**: http://45.11.51.2:8086
- **Utilisateur**: root
- **Mot de passe**: ngamba123

## 🗄️ Base de Données
- **Type**: MySQL 8.0
- **Nom**: 3tek
- **Utilisateur**: 3tek
- **Mot de passe**: ngamba123
- **Port**: 3309

## 🐳 Services Docker

### Conteneurs
- `3tek-mysql`: Base de données MySQL
- `3tek-app`: Application Symfony (PHP-FPM)
- `3tek-nginx`: Serveur web Nginx
- `3tek-phpmyadmin`: Interface d'administration MySQL

### Ports
- **8085**: Interface web principale
- **8086**: PhpMyAdmin
- **3309**: MySQL (accès direct)

## 📁 Structure
```
/opt/docker/3tek/
├── docker-compose.yml
├── Dockerfile
├── mysql/
│   └── my.cnf
├── nginx/
│   └── nginx.conf
├── scripts/
│   └── 3tek-manage.sh
└── src/ (après installation)
```

## 🔧 Configuration

### Variables d'environnement
- `APP_ENV`: prod
- `APP_SECRET`: your-secret-key-here-change-in-production
- `DATABASE_URL`: mysql://3tek:ngamba123@mysql:3306/3tek

### Volumes persistants
- `mysql-data`: Données MySQL
- `app-data`: Cache et logs Symfony

## 🆘 Dépannage

### Vérifier les logs
```bash
./scripts/3tek-manage.sh logs
```

### Redémarrer les services
```bash
./scripts/3tek-manage.sh restart
```

### Vérifier le statut
```bash
./scripts/3tek-manage.sh status
```

## 📝 Notes
- L'application utilise Symfony 7.3 avec PHP 8.2
- Base de données MySQL 8.0 avec authentification native
- Serveur web Nginx pour la performance
- PhpMyAdmin pour l'administration de la base de données
