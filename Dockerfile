FROM php:8.1-fpm

# Install system dependencies, PHP extensions (gd, soap, zip, etc.)...
# Copy source code
COPY . /var/www/html

# Install Magento dependencies inside the container
WORKDIR /var/www/html
RUN composer install --no-dev

# Set correct permissions, ownership, configs etc.
