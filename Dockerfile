# Laravel app image: PHP 8.4 + Apache with every extension the project needs.
FROM php:8.4-apache

# System libraries required to compile the PHP extensions below.
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git unzip curl \
        libpng-dev libjpeg62-turbo-dev libfreetype6-dev \
        libzip-dev libicu-dev libonig-dev libxml2-dev \
    && rm -rf /var/lib/apt/lists/*

# PHP extensions. mbstring, xml/dom, curl, ctype, fileinfo, tokenizer, openssl, pdo_sqlite
# are already part of the official image; these are the missing ones.
#  - pdo_mysql : MySQL database
#  - gd, zip, bcmath, intl, exif : common Laravel features (images, archives, decimals, i18n)
#  - pcntl     : lets `php artisan mqtt:listen` stop cleanly on SIGTERM
#  - opcache   : faster PHP
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_mysql gd zip bcmath intl exif pcntl opcache

# Composer.
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Apache: serve Laravel's public/ folder and allow .htaccess rewrites.
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN a2enmod rewrite headers \
    && sed -ri 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf \
    && sed -ri 's!AllowOverride None!AllowOverride All!g' /etc/apache2/apache2.conf \
    && echo 'ServerName localhost' > /etc/apache2/conf-available/servername.conf \
    && a2enconf servername

# PHP settings for development (uploads, memory, timezone).
RUN { \
        echo 'memory_limit=512M'; \
        echo 'upload_max_filesize=20M'; \
        echo 'post_max_size=20M'; \
        echo 'date.timezone=Asia/Jakarta'; \
        echo 'opcache.validate_timestamps=1'; \
        echo 'opcache.revalidate_freq=0'; \
    } > /usr/local/etc/php/conf.d/laravel.ini

WORKDIR /var/www/html

# Composer runs as root inside the container; silence its warning and keep its cache in the image.
ENV COMPOSER_ALLOW_SUPERUSER=1

# The source is bind-mounted at runtime, so storage/ and bootstrap/cache/ permissions are
# fixed by the entrypoint on every start instead of at build time.
COPY docker/entrypoint.sh /usr/local/bin/laravel-entrypoint
RUN chmod +x /usr/local/bin/laravel-entrypoint

EXPOSE 80
ENTRYPOINT ["laravel-entrypoint"]
CMD ["apache2-foreground"]
