# PHP 8.2 con Apache — imagen oficial
FROM php:8.2-apache

# Habilitar módulo mod_rewrite de Apache
RUN a2enmod rewrite

# Instalar extensiones necesarias
RUN docker-php-ext-install mysqli
RUN apt-get update && apt-get install -y libcurl4-openssl-dev && docker-php-ext-install curl

# Copiar archivos PHP del relay al servidor
COPY paciente.php /var/www/html/paciente.php
COPY vacunas.php  /var/www/html/vacunas.php

# Configurar Apache para que escuche en el puerto que Render asigna
# Render usa la variable PORT (por defecto 10000)
RUN sed -i 's/Listen 80/Listen ${PORT}/g' /etc/apache2/ports.conf && \
    sed -i 's/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/g' /etc/apache2/sites-enabled/000-default.conf

# Exponer el puerto
EXPOSE 10000

# Arrancar Apache en foreground
CMD ["apache2-foreground"]
