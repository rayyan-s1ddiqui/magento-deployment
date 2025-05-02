FROM phoenixmedia/magento:latest

# Install debugging & convenience tools
RUN apt-get update && apt-get install -y \
    vim \
    nano \
    curl \
    unzip \
    git \
    cron \
    supervisor \
    && rm -rf /var/lib/apt/lists/*

# Optional: Copy custom modules/themes
# COPY ./app/code/YourVendor/YourModule /var/www/html/app/code/YourVendor/YourModule

# Optional: Add your custom entrypoint or start scripts
COPY ./docker-entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

# Optional: Add cron job (Magento cron)
COPY ./crontab /etc/cron.d/magento-cron
RUN chmod 0644 /etc/cron.d/magento-cron && crontab /etc/cron.d/magento-cron

# Ensure permissions (Magento can be very permission-sensitive)
RUN chown -R www-data:www-data /var/www/html \
    && find /var/www/html -type d -exec chmod 755 {} \; \
    && find /var/www/html -type f -exec chmod 644 {} \;

ENTRYPOINT ["/entrypoint.sh"]


# Optional: Add custom config, modules, themes, etc.
# COPY ./src /var/www/html

# Make sure Apache runs in foreground
CMD ["apache2-foreground"]
