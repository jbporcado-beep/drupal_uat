FROM drupal:11.2.5-php8.4-apache-bookworm

# Install Composer dependencies
COPY composer.json composer.lock /opt/drupal/web/
RUN composer install --no-dev --optimize-autoloader --no-interaction
RUN composer require drush/drush

# Copy custom modules, themes, and other project files
COPY config/ /opt/drupal/config
COPY web/modules/ /opt/drupal/web/modules/
COPY web/libraries/ /opt/drupal/web/libraries/
COPY web/themes/ /opt/drupal/web/themes/
COPY web/sites/ /opt/drupal/web/sites/
COPY web/sites/default/docker.settings.php /opt/drupal/web/sites/default/settings.php
COPY docker-entrypoint.sh /

# Set permissions
RUN chown -R www-data:www-data /opt/drupal/web
RUN chmod 644 /opt/drupal/web/sites/default/settings.php
ENV SITE_UUID=d9c155f8-eee6-4702-a32a-0fa07caede88

ENTRYPOINT [ "/docker-entrypoint.sh" ]
