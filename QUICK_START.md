# 🚀 Démarrage Rapide - 3TEK avec Docker

## ⚡ En 3 étapes

### 1️⃣ Prérequis
- Installez [Docker Desktop](https://www.docker.com/products/docker-desktop) pour Windows
- Démarrez Docker Desktop

### 2️⃣ Lancer l'application

**Double-cliquez sur** `start-dev.bat`

Ou en ligne de commande :
```bash
docker compose -f compose.yaml -f compose.override.yaml up -d
```

### 3️⃣ Accéder à l'application

- **Application** : http://localhost:8080
- **PhpMyAdmin** : http://localhost:8081 (user: `root`, password: `ngamba123`)
- **Mailpit** : http://localhost:8025 (pour voir les emails)

## 🛑 Arrêter l'application

**Double-cliquez sur** `stop-dev.bat`

Ou en ligne de commande :
```bash
docker compose down
```

## 📝 Première utilisation

Après le premier démarrage, exécutez :

```bash
# Accéder au conteneur PHP
docker compose exec php bash

# Installer les dépendances
composer install

# Créer la base de données
php bin/console doctrine:database:create --if-not-exists

# Exécuter les migrations
php bin/console doctrine:migrations:migrate --no-interaction

# Installer les assets
php bin/console assets:install
```

Ou utilisez le Makefile (si vous avez Make installé) :
```bash
make install
```

## 🔧 Commandes Utiles

```bash
# Voir les logs
docker compose logs -f

# Accéder au shell PHP
docker compose exec php bash

# Exécuter une commande Symfony
docker compose exec php php bin/console [commande]

# Vider le cache
docker compose exec php php bin/console cache:clear

# Voir l'état des conteneurs
docker compose ps
```

## 📚 Documentation Complète

Pour plus de détails, consultez [DOCKER_README.md](DOCKER_README.md)

## ❓ Problèmes Courants

### Docker ne démarre pas
- Vérifiez que Docker Desktop est bien lancé
- Redémarrez Docker Desktop

### Port déjà utilisé
Si le port 8080 est déjà utilisé, modifiez dans `compose.override.yaml` :
```yaml
nginx:
  ports:
    - "8081:80"  # Changez 8080 en 8081
```

### Problèmes de permissions
```bash
docker compose exec -u root php chown -R www-data:www-data /var/www/html/var
docker compose exec -u root php chmod -R 775 /var/www/html/var
```

## 🎯 Prochaines Étapes

1. Créez votre premier utilisateur admin
2. Configurez les paramètres de l'application
3. Testez l'envoi d'emails (visible dans Mailpit)
4. Consultez la documentation complète pour le déploiement en production
