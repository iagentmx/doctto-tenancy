#########################################
# Base stage - común para todos
#########################################
FROM php:8.3-fpm AS base

# Instalar dependencias del sistema
RUN apt-get update && apt-get install -y \
    git \
    zip \
    unzip \
    libpq-dev \
    libzip-dev \
    postgresql-client \
    && docker-php-ext-install pdo_pgsql zip pcntl \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

# Instalar Composer
COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

# Copiar archivos de dependencias
COPY composer.json composer.lock ./

#########################################
# Development stage
#########################################
FROM base AS development

# Instalar Xdebug solo para desarrollo
RUN pecl install xdebug && docker-php-ext-enable xdebug

# Instalar dependencias (incluyendo dev)
RUN composer install --no-interaction --no-scripts --prefer-dist

# Copiar el resto del código
COPY . .

# Optimizar autoload y descubrir paquetes
RUN composer dump-autoload --optimize \
    && php artisan package:discover --ansi || true

# Copiar entrypoint
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 8000

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]

#########################################
# Production stage
#########################################
FROM base AS production

# Instalar solo dependencias de producción
RUN composer install --no-dev --no-interaction --no-scripts --prefer-dist --optimize-autoloader

# Copiar el resto del código
COPY . .

# Optimizaciones de producción
RUN composer dump-autoload --optimize --classmap-authoritative \
    && php artisan package:discover --ansi \
    && php artisan config:cache \
    && php artisan route:cache \
    && php artisan view:cache

# Ajustar permisos
RUN chown -R www-data:www-data storage bootstrap/cache

# Copiar entrypoint
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 9000

USER www-data

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["php-fpm"]