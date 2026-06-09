FROM php:8.4-fpm

# System deps + PHP extensions needed by Laravel + readability.php (dom/mbstring/xml are bundled)
RUN apt-get update && apt-get install -y --no-install-recommends \
        libzip-dev \
        libsqlite3-dev \
        unzip \
        git \
    && docker-php-ext-install pdo pdo_sqlite zip \
    && rm -rf /var/lib/apt/lists/*

# Composer (for running artisan/composer inside the container)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# On startup, make the bind-mounted writable dirs accessible to the www-data
# pool (host files arrive root-owned), then hand off to php-fpm.
COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm"]
