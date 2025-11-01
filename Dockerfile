FROM php:8.2-fpm

# Installation des dépendances système
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev libicu-dev \
    zip \
    unzip \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip intl

# Installation de Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Définition du répertoire de travail
WORKDIR /var/www/html

# Copie des fichiers de l'application
COPY . .

# Installation des dépendances PHP
RUN composer install --no-dev --optimize-autoloader

# Configuration des permissions - APPROCHE DÉFINITIVE
RUN chown -R www-data:www-data /var/www/html
RUN chmod -R 755 /var/www/html
RUN chmod -R 777 /var/www/html/var

# Création des répertoires de cache nécessaires avec permissions correctes
RUN mkdir -p /var/www/html/var/cache/prod/{asset_mapper,easyadmin,pools/system,twig}
RUN chown -R www-data:www-data /var/www/html/var/cache
RUN chmod -R 777 /var/www/html/var/cache

# Création des répertoires de logs avec permissions correctes
RUN mkdir -p /var/www/html/var/log
RUN chown -R www-data:www-data /var/www/html/var/log
RUN chmod -R 777 /var/www/html/var/log

# Script de démarrage pour corriger les permissions au démarrage
RUN echo '#!/bin/bash\nchown -R www-data:www-data /var/www/html/var\nchmod -R 777 /var/www/html/var\nmkdir -p /var/www/html/var/cache/prod/{asset_mapper,easyadmin,pools/system,twig}\nchown -R www-data:www-data /var/www/html/var/cache\nchmod -R 777 /var/www/html/var/cache\nphp-fpm' > /start.sh
RUN chmod +x /start.sh

# Exposition du port
EXPOSE 80

# Commande de démarrage
CMD ["/start.sh"]