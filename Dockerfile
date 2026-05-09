# RecettesPro (R-Pro) - Image de production
# PHP 8.2 + Apache pour servir l'application
FROM php:8.2-apache

# Extensions PHP nécessaires
# - mbstring : manipulation de chaînes (UTF-8 noms de communes/agents)
# - intl     : formatage nombres/dates en FR
# - zip      : utilisé par certaines dépendances Composer (Google API)
# - opcache  : performances PHP en production
RUN apt-get update && apt-get install -y \
    libicu-dev \
    libzip-dev \
    zip \
    unzip \
    && docker-php-ext-install -j$(nproc) \
        mbstring \
        intl \
        zip \
        opcache \
    && rm -rf /var/lib/apt/lists/*

# Activer mod_rewrite et mod_headers pour Apache
RUN a2enmod rewrite headers

# Copier la configuration Apache personnalisée
COPY apache.conf /etc/apache2/sites-available/000-default.conf

# Copier tout le code de l'application dans le DocumentRoot
COPY . /var/www/html/

# Permissions :
# - tout le code en lecture pour www-data
# - dossier data/ en lecture+écriture (CSV vivants : tickets_buffer, ticket_index)
RUN chown -R www-data:www-data /var/www/html \
    && find /var/www/html -type d -exec chmod 755 {} \; \
    && find /var/www/html -type f -exec chmod 644 {} \; \
    && chmod -R 775 /var/www/html/data \
    && chown -R www-data:www-data /var/www/html/data

# Configuration PHP de production
RUN { \
    echo 'display_errors = Off'; \
    echo 'display_startup_errors = Off'; \
    echo 'log_errors = On'; \
    echo 'error_log = /var/log/php_errors.log'; \
    echo 'expose_php = Off'; \
    echo 'session.cookie_httponly = 1'; \
    echo 'session.cookie_secure = 1'; \
    echo 'session.use_strict_mode = 1'; \
    echo 'date.timezone = Africa/Kinshasa'; \
    } > /usr/local/etc/php/conf.d/rpro-prod.ini

# Apache écoute sur le port 80
EXPOSE 80

# Lancement Apache au premier plan (requis pour Docker)
CMD ["apache2-foreground"]
