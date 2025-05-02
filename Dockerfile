FROM phoenixmedia/magento:latest

# Optional: Add custom config, modules, themes, etc.
# COPY ./src /var/www/html

# Make sure Apache runs in foreground
CMD ["apache2-foreground"]
