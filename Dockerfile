# Dockerfile
FROM php:8.2-apache

# Instalar dependencias necesarias
RUN apt-get update && apt-get install -y unzip git

# Instalar Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copiar archivos del proyecto
COPY . /var/www/html/

# Instalar dependencias de Composer
RUN composer install --no-dev --optimize-autoloader

# Exponer puerto 80
EXPOSE 80
