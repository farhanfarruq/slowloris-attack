FROM php:8.3-cli

RUN apt-get update && apt-get install -y     default-mysql-client     libzip-dev     libonig-dev     tshark     unzip     && docker-php-ext-install pdo pdo_mysql zip mbstring     && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY docker/php-upload.ini /usr/local/etc/php/conf.d/99-upload.ini

EXPOSE 8000

CMD ["sh", "-c", "php artisan migrate --force && php artisan db:seed --force && php artisan serve --host=0.0.0.0 --port=8000"]
