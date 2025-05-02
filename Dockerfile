FROM php:8.1-fpm

WORKDIR /var/www/html

# System dependencies
RUN apt-get update && apt-get install -y \
    git zip unzip libpng-dev libonig-dev libxml2-dev \
    libzip-dev libicu-dev libxslt-dev libjpeg-dev libfreetype6-dev \
    imagemagick cron nano libcurl4-openssl-dev curl \
    && docker-php-ext-install pdo_mysql mbstring zip exif pcntl bcmath intl soap xsl curl gd

# Install Composer
COPY --from=composer:2.6 /usr/bin/composer /usr/bin/composer

# Set PHP config
COPY ./php.ini /usr/local/etc/php/php.ini

# Copy Magento source (optional; better to mount a volume or pull from Git in CI)
COPY . .

# Set permissions
RUN chown -R www-data:www-data /var/www/html && \
    chmod +x bin/magento

USER www-data

EXPOSE 9000
CMD ["php-fpm"]
