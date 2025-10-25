.PHONY: help build up down restart logs shell db-shell clean install migrate dev prod

help: ## Affiche cette aide
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-20s\033[0m %s\n", $$1, $$2}'

# Développement
dev: ## Lance l'environnement de développement
	docker compose -f compose.yaml -f compose.override.yaml up -d

dev-build: ## Reconstruit et lance l'environnement de développement
	docker compose -f compose.yaml -f compose.override.yaml up -d --build

dev-down: ## Arrête l'environnement de développement
	docker compose -f compose.yaml -f compose.override.yaml down

# Production
prod: ## Lance l'environnement de production
	docker compose -f docker-compose.prod.yaml up -d

prod-build: ## Reconstruit et lance l'environnement de production
	docker compose -f docker-compose.prod.yaml up -d --build

prod-down: ## Arrête l'environnement de production
	docker compose -f docker-compose.prod.yaml down

# Commandes générales
build: ## Reconstruit les images Docker
	docker compose build

up: ## Démarre les conteneurs
	docker compose up -d

down: ## Arrête les conteneurs
	docker compose down

restart: ## Redémarre les conteneurs
	docker compose restart

logs: ## Affiche les logs
	docker compose logs -f

logs-php: ## Affiche les logs PHP
	docker compose logs -f php

logs-nginx: ## Affiche les logs Nginx
	docker compose logs -f nginx

# Accès aux conteneurs
shell: ## Accède au shell du conteneur PHP
	docker compose exec php bash

shell-root: ## Accède au shell du conteneur PHP en root
	docker compose exec -u root php bash

db-shell: ## Accède au shell MySQL
	docker compose exec database mysql -u root -p

# Base de données
migrate: ## Exécute les migrations
	docker compose exec php php bin/console doctrine:migrations:migrate --no-interaction

db-create: ## Crée la base de données
	docker compose exec php php bin/console doctrine:database:create --if-not-exists

db-reset: ## Réinitialise la base de données (ATTENTION: supprime toutes les données)
	docker compose exec php php bin/console doctrine:database:drop --force --if-exists
	docker compose exec php php bin/console doctrine:database:create
	docker compose exec php php bin/console doctrine:migrations:migrate --no-interaction

# Cache et assets
cache-clear: ## Vide le cache
	docker compose exec php php bin/console cache:clear

assets: ## Installe les assets
	docker compose exec php php bin/console assets:install

# Installation
install: ## Installation complète du projet
	@echo "Installation des dépendances..."
	docker compose exec php composer install
	@echo "Création de la base de données..."
	docker compose exec php php bin/console doctrine:database:create --if-not-exists
	@echo "Exécution des migrations..."
	docker compose exec php php bin/console doctrine:migrations:migrate --no-interaction
	@echo "Installation des assets..."
	docker compose exec php php bin/console assets:install
	@echo "✅ Installation terminée!"

# Nettoyage
clean: ## Nettoie les conteneurs, volumes et images
	docker compose down -v
	docker system prune -f

clean-all: ## Nettoie tout (conteneurs, volumes, images, cache)
	docker compose down -v --rmi all
	docker system prune -af --volumes

# Tests
test: ## Lance les tests
	docker compose exec php php bin/phpunit

# Composer
composer-install: ## Installe les dépendances Composer
	docker compose exec php composer install

composer-update: ## Met à jour les dépendances Composer
	docker compose exec php composer update

# Permissions
fix-permissions: ## Corrige les permissions des fichiers
	docker compose exec -u root php chown -R www-data:www-data /var/www/html/var
	docker compose exec -u root php chmod -R 775 /var/www/html/var
	docker compose exec -u root php chown -R www-data:www-data /var/www/html/public/uploads
	docker compose exec -u root php chmod -R 775 /var/www/html/public/uploads
