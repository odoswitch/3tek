# 🚀 3TEK - Application Symfony 7.3

Application web développée avec Symfony 7.3, déployable avec Docker.

## 📋 Prérequis

- Docker Desktop (Windows/Mac) ou Docker Engine (Linux)
- Docker Compose 2.0+
- Git

## 🐳 Démarrage avec Docker (Recommandé)

### Windows

1. **Vérifier l'environnement** :
   ```bash
   # Double-cliquez sur check-docker.bat
   ```

2. **Démarrer l'application** :
   ```bash
   # Double-cliquez sur start-dev.bat
   ```

3. **Accéder à l'application** :
   - Application : http://localhost:8080
   - PhpMyAdmin : http://localhost:8081
   - Mailpit : http://localhost:8025

### Linux/Mac

```bash
# Démarrer
make dev

# Ou sans Make
docker compose -f compose.yaml -f compose.override.yaml up -d
```

## 📚 Documentation

- **[QUICK_START.md](QUICK_START.md)** - Démarrage rapide (5 minutes)
- **[DOCKER_README.md](DOCKER_README.md)** - Documentation Docker complète
- **[DEPLOIEMENT_DOCKER.md](DEPLOIEMENT_DOCKER.md)** - Guide de déploiement
- **[LISEZMOI_DOCKER.txt](LISEZMOI_DOCKER.txt)** - Aide rapide en français

## 🛠️ Technologies

- **Framework** : Symfony 7.3
- **PHP** : 8.2
- **Base de données** : MySQL 8.0
- **Serveur web** : Nginx
- **Containerisation** : Docker & Docker Compose

## 📦 Installation Complète

```bash
# 1. Démarrer les conteneurs
make dev

# 2. Installer les dépendances et initialiser la base
make install

# 3. (Optionnel) Charger des données de test
docker compose exec php php bin/console doctrine:fixtures:load
```

## 🔧 Commandes Utiles

```bash
make help              # Liste toutes les commandes
make dev               # Démarrer en développement
make logs              # Voir les logs
make shell             # Accéder au shell PHP
make migrate           # Exécuter les migrations
make cache-clear       # Vider le cache
```

## 🚀 Déploiement en Production

Consultez [DEPLOIEMENT_DOCKER.md](DEPLOIEMENT_DOCKER.md) pour les instructions détaillées.

```bash
# 1. Configurer l'environnement
cp .env.prod.example .env.prod
# Éditer .env.prod avec vos valeurs

# 2. Déployer
docker compose -f docker-compose.prod.yaml --env-file .env.prod up -d
```

## 📝 Structure du Projet

```
3tek/
├── config/              # Configuration Symfony
├── public/              # Point d'entrée web
├── src/                 # Code source
├── templates/           # Templates Twig
├── var/                 # Cache et logs
├── Dockerfile           # Image Docker
├── compose.yaml         # Docker Compose
├── Makefile            # Commandes simplifiées
└── README.md           # Ce fichier
```

## 🆘 Support

En cas de problème :

1. Vérifiez les logs : `make logs`
2. Consultez la documentation dans les fichiers MD
3. Vérifiez que Docker Desktop est lancé
4. Exécutez `check-docker.bat` pour diagnostiquer

## 📄 Licence

Propriétaire
