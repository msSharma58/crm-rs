FROM php:8.4-fpm-bookworm

RUN apt-get update && apt-get install -y --no-install-recommends \
    git curl unzip libzip-dev libpng-dev libonig-dev libxml2-dev \
    libicu-dev $PHPIZE_DEPS \
    && docker-php-ext-install pdo_mysql mbstring zip bcmath intl gd \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apt-get purge -y $PHPIZE_DEPS \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY docker/php/www.conf /usr/local/etc/php-fpm.d/www.conf

RUN useradd -G www-data,root -u 1000 -d /home/homora homora \
    && mkdir -p /home/homora \
    && chown -R homora:homora /home/homora

EXPOSE 9000
CMD ["php-fpm"]
