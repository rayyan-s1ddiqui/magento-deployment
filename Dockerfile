FROM php:8.4-fpm
RUN apt-get update && apt-get install -y \
    libpng-dev libzip-dev libxml2-dev \
    && docker-php-ext-install pdo_mysql zip gd soap \
    && pecl install redis && docker-php-ext-enable redis
WORKDIR /var/www/html
COPY . .
RUN chown -R www-data:www-data /var/www/html
