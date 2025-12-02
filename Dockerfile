# Imagen base con Apache + PHP 8.2
FROM php:8.2-apache

# Instala extensiones necesarias para MySQL
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Copia tu proyecto al contenedor
COPY . /var/www/html/

# Da permisos a Apache
RUN chown -R www-data:www-data /var/www/html

# Expone el puerto
EXPOSE 80
