# Build
FROM composer:2 AS builder

WORKDIR /app
COPY . .
RUN composer install \
    --no-dev \
    --optimize-autoloader \
    --ignore-platform-reqs

# Runtime
FROM dunglas/frankenphp:php8.3-alpine

RUN install-php-extensions pdo_mysql
WORKDIR /app
COPY --from=builder /app /app

RUN chown -R www-data:www-data storage bootstrap/cache

EXPOSE 8000
CMD ["php", "artisan", "serve", "--host=0.0.0.0"]