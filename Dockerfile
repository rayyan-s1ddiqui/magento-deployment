# Use the official PHP image with extensions
FROM php:8.1-fpm

# Set working directory
WORKDIR /var/www/html

# Install dependencies
RUN apt-get update && apt-get install -y \
    git zip unzip libpng-dev libonig-dev libxml2-dev \
    libzip-dev libicu-dev libxslt-dev libjpeg-dev libfreetype6-dev \
    libmcrypt-dev libmagickwand-dev imagemagick \
    cron nano gnupg2 libcurl4-openssl-dev curl \
    && docker-php-ext-install pdo_mysql mbstring zip exif pcntl bcmath intl soap xsl curl gd

# Install Composer
COPY --from=composer:2.6 /usr/bin/composer /usr/bin/composer

# Install Magento CLI dependencies
RUN curl -sS https://getcomposer.org/installer | php && mv composer.phar /usr/local/bin/composer

# PHP configuration
COPY ./php.ini /usr/local/etc/php/php.ini

# Copy project files
COPY . .

# Set permissions
RUN chown -R www-data:www-data /var/www/html && \
    find var generated vendor pub/static pub/media app/etc -type f -exec chmod g+w {} + && \
    find var generated vendor pub/static pub/media app/etc -type d -exec chmod g+ws {} + && \
    chmod u+x bin/magento

USER www-data

RUN apt-get update && \
    apt-get install -y curl unzip && \
    curl "https://awscli.amazonaws.com/awscli-exe-linux-x86_64.zip" -o "awscliv2.zip" && \
    unzip -o awscliv2.zip && \
    ./aws/install && \
    rm -rf awscliv2.zip aws

# Expose port
EXPOSE 9000

CMD ["php-fpm"]
