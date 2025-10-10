FROM drupal:11.2.5-php8.4-apache-bookworm

# Install Composer dependencies
COPY composer.json composer.lock /opt/drupal/web/
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Copy custom modules, themes, and other project files
COPY config/ /opt/drupal/config
COPY web/modules/ /opt/drupal/web/modules/
COPY web/themes/ /opt/drupal/web/themes/
COPY web/sites/ /opt/drupal/web/sites/
COPY web/sites/default/docker.settings.php /opt/drupal/web/sites/default/settings.php

# Set permissions
RUN chown -R www-data:www-data /opt/drupal/web
RUN chmod 644 /opt/drupal/web/sites/default/settings.php