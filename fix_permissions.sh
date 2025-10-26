#!/bin/bash
echo "=== CORRECTION DÉFINITIVE DES PERMISSIONS ==="

# Arrêter les conteneurs
echo "Arrêt des conteneurs..."
docker compose down

# Supprimer complètement le cache
echo "Suppression du cache..."
docker compose run --rm php rm -rf /var/www/html/var/cache/*

# Redémarrer les conteneurs
echo "Redémarrage des conteneurs..."
docker compose up -d

# Attendre que les conteneurs soient prêts
echo "Attente du démarrage..."
sleep 10

# Corriger les permissions
echo "Correction des permissions..."
docker compose exec php chown -R www-data:www-data /var/www/html/var
docker compose exec php chmod -R 777 /var/www/html/var

# Vider le cache
echo "Vidage du cache..."
docker compose exec php php bin/console cache:clear --env=dev

echo "=== CORRECTION TERMINÉE ==="
echo "L'application devrait maintenant fonctionner."
