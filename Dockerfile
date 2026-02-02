FROM drupal:11.2.5-php8.3-apache-bookworm

# Install Composer dependencies

COPY composer.json composer.lock /opt/drupal/
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Copy custom modules, themes, and other project files
COPY config/ /opt/drupal/config
COPY web/modules/ /opt/drupal/web/modules/
COPY web/libraries/ /opt/drupal/web/libraries/
COPY web/themes/ /opt/drupal/web/themes/
COPY web/sites/ /opt/drupal/web/sites/
COPY web/sites/default/docker.settings.php /opt/drupal/web/sites/default/settings.php
COPY docker-entrypoint.sh /

RUN apt-get update \
    && apt-get install -y --no-install-recommends gnupg libssl-dev ca-certificates \
    && docker-php-ext-configure ftp --with-openssl-dir=/usr \
    && docker-php-ext-install -j"$(nproc)" ftp \
    && rm -rf /var/lib/apt/lists/*
RUN mkdir -p /var/www/.gnupg \
    && chown -R www-data:www-data /var/www/.gnupg \
    && chmod 700 /var/www/.gnupg

COPY CICPublicProdKey2023-2025.asc CICPublicProdKey2023-2025.asc

# Set permissions
RUN chown -R www-data:www-data /opt/drupal/web
RUN chmod 644 /opt/drupal/web/sites/default/settings.php
ENV SITE_UUID=d9c155f8-eee6-4702-a32a-0fa07caede88
ENV GNUPGHOME=/var/www/.gnupg

ENTRYPOINT [ "/docker-entrypoint.sh" ]
