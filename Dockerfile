# Stage 1: Build dependencies
# Note: Les avertissements de vulnérabilité proviennent de l'image PHP officielle
# Mettez régulièrement à jour avec: docker pull php:8.2-fpm
FROM php:8.2-fpm AS builder

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libzip-dev \
    libicu-dev \
    && docker-php-ext-configure intl \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip intl \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy composer files first for better caching
COPY composer.json composer.lock symfony.lock ./

# Install PHP dependencies
RUN composer install --no-dev --no-interaction --no-scripts --optimize-autoloader --prefer-dist

# Stage 2: Production image
FROM php:8.2-fpm

# Install runtime dependencies only
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libicu-dev \
    default-mysql-client \
    && docker-php-ext-configure intl \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip intl \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Set working directory
WORKDIR /var/www/html

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy vendor from builder
COPY --from=builder /var/www/html/vendor ./vendor

# Copy application files
COPY . .

# Copy custom PHP configuration
COPY php-custom.ini /usr/local/etc/php/conf.d/php-custom.ini

# Create necessary directories
RUN mkdir -p var/cache var/log var/sessions public/uploads \
    && chown -R www-data:www-data var public/uploads \
    && chmod -R 775 var public/uploads

# Copy entrypoint script
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 9000

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["php-fpm"]
