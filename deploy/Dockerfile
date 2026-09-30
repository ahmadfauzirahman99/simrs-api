FROM serversideup/php:8.4-fpm-nginx
USER root
RUN install-php-extensions intl
USER www-data
COPY --chown=www-data:www-data src/ /var/www/html
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-progress \
 && rm -rf /var/www/html/storage/*
