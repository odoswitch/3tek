# 🐳 Guide de Déploiement Docker - 3TEK

Ce guide vous explique comment déployer l'application 3TEK avec Docker en développement et en production.

## 📋 Prérequis

- Docker Engine 20.10+
- Docker Compose 2.0+
- Make (optionnel, pour utiliser les commandes simplifiées)

## 🚀 Démarrage Rapide

### Développement

```bash
# Avec Make (recommandé)
make dev

# Sans Make
docker compose -f compose.yaml -f compose.override.yaml up -d
```

L'application sera accessible sur :
- **Application** : http://localhost:8080
- **PhpMyAdmin** : http://localhost:8081
- **Mailpit** : http://localhost:8025

### Production

```bash
# 1. Copier et configurer les variables d'environnement
cp .env.prod.example .env.prod

# 2. Éditer .env.prod avec vos valeurs de production
# IMPORTANT: Changez tous les mots de passe et clés secrètes !

# 3. Lancer l'environnement de production
docker compose -f docker-compose.prod.yaml --env-file .env.prod up -d
```

## 📁 Structure des Fichiers Docker

```
.
├── Dockerfile                    # Image PHP multi-stage optimisée
├── docker-entrypoint.sh         # Script d'initialisation automatique
├── compose.yaml                 # Configuration Docker Compose principale
├── compose.override.yaml        # Surcharges pour le développement
├── docker-compose.prod.yaml     # Configuration pour la production
├── nginx.conf                   # Configuration Nginx
├── php-custom.ini              # Configuration PHP personnalisée
├── .dockerignore               # Fichiers exclus de l'image Docker
├── .env                        # Variables d'environnement (dev)
├── .env.prod.example          # Template pour la production
└── Makefile                   # Commandes simplifiées
```

## 🛠️ Commandes Disponibles

### Avec Make

```bash
make help              # Affiche toutes les commandes disponibles
make dev               # Lance l'environnement de développement
make dev-build         # Reconstruit et lance le dev
make prod              # Lance l'environnement de production
make prod-build        # Reconstruit et lance la prod
make logs              # Affiche tous les logs
make logs-php          # Logs PHP uniquement
make shell             # Accède au shell du conteneur PHP
make db-shell          # Accède au shell MySQL
make migrate           # Exécute les migrations
make cache-clear       # Vide le cache Symfony
make install           # Installation complète du projet
make clean             # Nettoie les conteneurs et volumes
make fix-permissions   # Corrige les permissions
```

### Sans Make

```bash
# Démarrer
docker compose up -d

# Arrêter
docker compose down

# Voir les logs
docker compose logs -f

# Accéder au shell PHP
docker compose exec php bash

# Exécuter une commande Symfony
docker compose exec php php bin/console [commande]

# Migrations
docker compose exec php php bin/console doctrine:migrations:migrate

# Vider le cache
docker compose exec php php bin/console cache:clear
```

## 🔧 Configuration

### Variables d'Environnement

#### Développement (.env)
Le fichier `.env` est déjà configuré pour le développement local.

#### Production (.env.prod)
Créez un fichier `.env.prod` basé sur `.env.prod.example` :

```bash
cp .env.prod.example .env.prod
```

**⚠️ IMPORTANT** : Modifiez ces valeurs en production :

```env
# Générez une nouvelle clé secrète (32 caractères aléatoires)
APP_SECRET=CHANGEZ_CETTE_CLE_SECRETE

# Configurez votre base de données
MYSQL_ROOT_PASSWORD=un_mot_de_passe_fort
MYSQL_PASSWORD=un_autre_mot_de_passe_fort
DATABASE_URL="mysql://app_user:mot_de_passe@database:3306/3tek?serverVersion=8.0"

# Configurez votre serveur SMTP
MAILER_DSN=smtp://user:pass@smtp.example.com:587?encryption=tls
MAILER_FROM=noreply@votredomaine.com
```

### Ports Utilisés

#### Développement
- `8080` : Application web (Nginx)
- `8081` : PhpMyAdmin
- `8025` : Mailpit (interface web)
- `1025` : Mailpit (SMTP)
- `3306` : MySQL (exposé pour accès externe)

#### Production
- `80` : Application web HTTP
- `443` : Application web HTTPS (si SSL configuré)
- `3306` : MySQL (non exposé par défaut)

## 📦 Installation Complète

### Première Installation

```bash
# 1. Cloner le projet
git clone [url-du-repo]
cd 3tek

# 2. Copier le fichier d'environnement
cp .env.example .env

# 3. Démarrer les conteneurs
make dev-build

# 4. Installer le projet
make install

# 5. (Optionnel) Charger des données de test
docker compose exec php php bin/console doctrine:fixtures:load
```

### Mise à Jour

```bash
# 1. Récupérer les dernières modifications
git pull

# 2. Reconstruire les images
make dev-build

# 3. Exécuter les migrations
make migrate

# 4. Vider le cache
make cache-clear
```

## 🔒 Sécurité en Production

### Checklist de Sécurité

- [ ] Changez `APP_SECRET` avec une valeur unique et aléatoire
- [ ] Utilisez des mots de passe forts pour MySQL
- [ ] Configurez `APP_DEBUG=0` en production
- [ ] Utilisez HTTPS avec des certificats SSL valides
- [ ] Limitez l'accès aux ports (ne pas exposer MySQL)
- [ ] Configurez un firewall
- [ ] Activez les sauvegardes automatiques de la base de données
- [ ] Utilisez des volumes Docker pour les données persistantes

### Configuration SSL/HTTPS

Pour activer HTTPS :

1. Placez vos certificats SSL dans un dossier `ssl/`
2. Décommentez les lignes SSL dans `docker-compose.prod.yaml`
3. Configurez Nginx pour utiliser SSL dans `nginx.conf`

## 🗄️ Gestion de la Base de Données

### Sauvegardes

```bash
# Créer une sauvegarde
docker compose exec database mysqldump -u root -p3tek > backup_$(date +%Y%m%d_%H%M%S).sql

# Restaurer une sauvegarde
docker compose exec -T database mysql -u root -p3tek < backup.sql
```

### Réinitialiser la Base

```bash
# ⚠️ ATTENTION : Supprime toutes les données !
make db-reset
```

## 🐛 Dépannage

### Les conteneurs ne démarrent pas

```bash
# Vérifier les logs
docker compose logs

# Vérifier l'état des conteneurs
docker compose ps

# Reconstruire complètement
docker compose down -v
docker compose up -d --build
```

### Problèmes de permissions

```bash
# Corriger les permissions
make fix-permissions

# Ou manuellement
docker compose exec -u root php chown -R www-data:www-data /var/www/html/var
docker compose exec -u root php chmod -R 775 /var/www/html/var
```

### La base de données ne se connecte pas

```bash
# Vérifier que le conteneur MySQL est démarré
docker compose ps database

# Vérifier les logs MySQL
docker compose logs database

# Tester la connexion
docker compose exec php php bin/console dbal:run-sql "SELECT 1"
```

### Erreur "Composer install failed"

```bash
# Installer manuellement
docker compose exec php composer install

# Ou avec plus de mémoire
docker compose exec php php -d memory_limit=-1 /usr/bin/composer install
```

## 📊 Monitoring et Logs

### Voir les logs en temps réel

```bash
# Tous les services
make logs

# PHP uniquement
make logs-php

# Nginx uniquement
make logs-nginx

# Base de données
docker compose logs -f database
```

### Accéder aux fichiers de logs

Les logs Symfony sont dans `var/log/` :
```bash
docker compose exec php tail -f var/log/dev.log
docker compose exec php tail -f var/log/prod.log
```

## 🚀 Déploiement sur un Serveur

### Option 1 : Docker sur VPS

```bash
# 1. Installer Docker sur le serveur
curl -fsSL https://get.docker.com -o get-docker.sh
sh get-docker.sh

# 2. Cloner le projet
git clone [url] /var/www/3tek
cd /var/www/3tek

# 3. Configurer l'environnement
cp .env.prod.example .env.prod
nano .env.prod

# 4. Lancer en production
docker compose -f docker-compose.prod.yaml --env-file .env.prod up -d
```

### Option 2 : Docker Swarm (Haute disponibilité)

```bash
# Initialiser Swarm
docker swarm init

# Déployer la stack
docker stack deploy -c docker-compose.prod.yaml 3tek
```

## 📝 Notes Importantes

1. **Volumes** : Les données MySQL sont stockées dans un volume Docker nommé `database_data`
2. **Uploads** : Les fichiers uploadés sont dans `public/uploads/`
3. **Cache** : Le cache Symfony est dans `var/cache/`
4. **Logs** : Les logs sont dans `var/log/`

## 🆘 Support

Pour toute question ou problème :
1. Vérifiez les logs : `make logs`
2. Consultez la documentation Symfony : https://symfony.com/doc
3. Vérifiez la documentation Docker : https://docs.docker.com

## 📚 Ressources

- [Documentation Symfony](https://symfony.com/doc)
- [Documentation Docker](https://docs.docker.com)
- [Documentation Docker Compose](https://docs.docker.com/compose)
- [Best Practices Docker](https://docs.docker.com/develop/dev-best-practices)
