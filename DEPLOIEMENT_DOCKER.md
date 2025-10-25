# 🐳 Déploiement Docker - Configuration Complète

## ✅ Fichiers Créés

Votre environnement Docker est maintenant configuré avec les fichiers suivants :

### 📋 Fichiers Principaux

1. **Dockerfile** - Image PHP optimisée avec multi-stage build
2. **docker-entrypoint.sh** - Script d'initialisation automatique
3. **compose.yaml** - Configuration Docker Compose principale
4. **compose.override.yaml** - Surcharges pour le développement
5. **docker-compose.prod.yaml** - Configuration production
6. **nginx.conf** - Configuration Nginx
7. **php-custom.ini** - Configuration PHP personnalisée

### 📝 Documentation

8. **DOCKER_README.md** - Documentation complète
9. **QUICK_START.md** - Guide de démarrage rapide
10. **DEPLOIEMENT_DOCKER.md** - Ce fichier

### 🛠️ Utilitaires

11. **Makefile** - Commandes simplifiées
12. **start-dev.bat** - Script de démarrage Windows
13. **stop-dev.bat** - Script d'arrêt Windows
14. **.env.prod.example** - Template pour la production
15. **.dockerignore** - Optimisation de l'image

## 🚀 Démarrage Immédiat

### Pour Développement

**Windows** :
```bash
# Double-cliquez sur start-dev.bat
# OU
docker compose -f compose.yaml -f compose.override.yaml up -d
```

**Linux/Mac** :
```bash
make dev
# OU
docker compose -f compose.yaml -f compose.override.yaml up -d
```

### Pour Production

```bash
# 1. Configurer l'environnement
cp .env.prod.example .env.prod
nano .env.prod  # Modifier les valeurs

# 2. Lancer
docker compose -f docker-compose.prod.yaml --env-file .env.prod up -d
```

## 📊 Architecture Docker

```
┌─────────────────────────────────────────┐
│         Nginx (Port 8080/80)            │
│     Serveur Web + Reverse Proxy         │
└──────────────┬──────────────────────────┘
               │
               ▼
┌─────────────────────────────────────────┐
│         PHP-FPM 8.2                     │
│     Application Symfony 7.3             │
└──────────────┬──────────────────────────┘
               │
               ▼
┌─────────────────────────────────────────┐
│         MySQL 8.0                       │
│     Base de données                     │
└─────────────────────────────────────────┘

Services additionnels (dev) :
- PhpMyAdmin (Port 8081)
- Mailpit (Ports 8025/1025)
```

## 🔧 Caractéristiques

### Dockerfile Multi-Stage

- **Stage 1 (Builder)** : Installation des dépendances
- **Stage 2 (Production)** : Image légère avec seulement le nécessaire
- Optimisation de la taille de l'image
- Meilleure sécurité (moins de packages)

### Auto-Initialisation

Le script `docker-entrypoint.sh` :
- ✅ Attend que la base de données soit prête
- ✅ Crée les répertoires nécessaires
- ✅ Configure les permissions
- ✅ Exécute les migrations automatiquement
- ✅ Vide et réchauffe le cache
- ✅ Installe les assets

### Environnements Séparés

- **Développement** : Hot-reload, debug, outils de dev
- **Production** : Optimisé, sécurisé, performant

## 📦 Services Disponibles

### Développement

| Service | Port | Description |
|---------|------|-------------|
| Nginx | 8080 | Application web |
| PHP-FPM | 9000 | Traitement PHP |
| MySQL | 3306 | Base de données |
| PhpMyAdmin | 8081 | Interface MySQL |
| Mailpit | 8025 | Interface emails |
| Mailpit SMTP | 1025 | Serveur SMTP test |

### Production

| Service | Port | Description |
|---------|------|-------------|
| Nginx | 80/443 | Application web |
| PHP-FPM | 9000 | Traitement PHP |
| MySQL | - | Base de données (interne) |

## 🎯 Commandes Essentielles

### Gestion des Conteneurs

```bash
# Démarrer
make dev              # Développement
make prod             # Production

# Arrêter
make dev-down         # Développement
make prod-down        # Production

# Reconstruire
make dev-build        # Développement
make prod-build       # Production

# Voir les logs
make logs             # Tous les services
make logs-php         # PHP uniquement
make logs-nginx       # Nginx uniquement
```

### Base de Données

```bash
# Migrations
make migrate

# Créer la base
make db-create

# Réinitialiser (⚠️ supprime les données)
make db-reset

# Accéder au shell MySQL
make db-shell
```

### Application

```bash
# Vider le cache
make cache-clear

# Installer les assets
make assets

# Installation complète
make install

# Accéder au shell PHP
make shell
```

### Maintenance

```bash
# Corriger les permissions
make fix-permissions

# Nettoyer
make clean            # Conteneurs et volumes
make clean-all        # Tout (images incluses)
```

## 🔒 Sécurité Production

### Checklist Avant Déploiement

- [ ] Modifier `APP_SECRET` dans `.env.prod`
- [ ] Changer tous les mots de passe MySQL
- [ ] Configurer `APP_DEBUG=0`
- [ ] Configurer le SMTP réel
- [ ] Activer HTTPS/SSL
- [ ] Configurer un firewall
- [ ] Limiter l'accès aux ports
- [ ] Configurer les sauvegardes
- [ ] Tester l'envoi d'emails
- [ ] Vérifier les logs

### Génération de Secrets

```bash
# Générer un APP_SECRET
php -r "echo bin2hex(random_bytes(16));"

# Générer un mot de passe fort
openssl rand -base64 32
```

## 📈 Performance

### Optimisations Incluses

1. **Multi-stage build** : Image finale légère
2. **Cache Docker** : Build plus rapide
3. **OPcache** : Cache PHP activé
4. **Nginx** : Buffers optimisés pour gros fichiers
5. **Composer** : Autoloader optimisé
6. **Symfony** : Cache préchauffé

### Monitoring

```bash
# Utilisation des ressources
docker stats

# Espace disque
docker system df

# Logs en temps réel
docker compose logs -f --tail=100
```

## 🔄 Workflow de Développement

### Développement Local

```bash
# 1. Démarrer l'environnement
make dev

# 2. Installer les dépendances
make install

# 3. Développer...
# Les changements de code sont automatiquement pris en compte

# 4. Voir les logs
make logs

# 5. Arrêter
make dev-down
```

### Mise en Production

```bash
# 1. Tester localement
make dev
# ... tests ...

# 2. Configurer la production
cp .env.prod.example .env.prod
# Éditer .env.prod

# 3. Déployer
make prod-build

# 4. Vérifier
docker compose -f docker-compose.prod.yaml ps
docker compose -f docker-compose.prod.yaml logs
```

## 🐛 Dépannage

### Problème : Conteneur ne démarre pas

```bash
# Voir les logs
docker compose logs [service]

# Reconstruire
docker compose up -d --build --force-recreate
```

### Problème : Base de données inaccessible

```bash
# Vérifier l'état
docker compose ps database

# Tester la connexion
docker compose exec php php bin/console dbal:run-sql "SELECT 1"

# Redémarrer MySQL
docker compose restart database
```

### Problème : Permissions

```bash
# Corriger automatiquement
make fix-permissions

# Ou manuellement
docker compose exec -u root php chown -R www-data:www-data /var/www/html/var
docker compose exec -u root php chmod -R 775 /var/www/html/var
```

### Problème : Cache

```bash
# Vider le cache
make cache-clear

# Ou forcer
docker compose exec php rm -rf var/cache/*
docker compose exec php php bin/console cache:clear
```

## 📚 Ressources

- [Documentation complète](DOCKER_README.md)
- [Guide de démarrage rapide](QUICK_START.md)
- [Documentation Symfony](https://symfony.com/doc)
- [Documentation Docker](https://docs.docker.com)

## ✨ Fonctionnalités Avancées

### Sauvegardes Automatiques

Créez un cron job pour les sauvegardes :

```bash
# Sauvegarde quotidienne à 2h du matin
0 2 * * * cd /path/to/project && docker compose exec -T database mysqldump -u root -p${MYSQL_ROOT_PASSWORD} 3tek > backup_$(date +\%Y\%m\%d).sql
```

### Scaling

```bash
# Augmenter le nombre de workers PHP
docker compose up -d --scale php=3
```

### Health Checks

Les health checks sont configurés pour MySQL. Ajoutez-en pour PHP si nécessaire.

## 🎉 Conclusion

Votre environnement Docker est maintenant prêt ! 

**Prochaines étapes** :
1. Lisez [QUICK_START.md](QUICK_START.md) pour démarrer rapidement
2. Consultez [DOCKER_README.md](DOCKER_README.md) pour les détails
3. Lancez `make dev` ou double-cliquez sur `start-dev.bat`
4. Accédez à http://localhost:8080

Bon développement ! 🚀
