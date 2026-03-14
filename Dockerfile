FROM dunglas/frankenphp:php8.4

RUN apt-get update \
    && apt-get install -y git unzip libzip-dev libicu-dev libonig-dev \
    && docker-php-ext-install pdo_mysql bcmath intl \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . .

RUN git config --global --add safe.directory /var/www/html

RUN composer install --no-interaction --prefer-dist

EXPOSE 8000

CMD ["sh", "-c", "php artisan key:generate --force && php artisan migrate --force && php artisan db:seed --force && frankenphp php-server --listen 0.0.0.0:8000 --root /var/www/html/public"]
