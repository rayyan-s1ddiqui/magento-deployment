FROM bitnami/magento:2.4

# Install Node.js (required for compiling assets)
USER root
RUN install_packages curl gnupg \
  && curl -fsSL https://deb.nodesource.com/setup_18.x | bash - \
  && apt-get install -y nodejs \
  && npm install -g grunt-cli yarn

# Optional: Add dev tools Magento sometimes cries about
RUN install_packages git unzip vim cron

# Back to non-root user for security (Bitnami style)
USER 1001

