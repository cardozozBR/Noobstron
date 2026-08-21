FROM php:8.4-cli

RUN apt-get update \
    && apt-get install -y \
        git \
        unzip \
        libpq-dev \
        libzip-dev \
        libicu-dev \
    && docker-php-ext-install \
        pdo_pgsql \
        pgsql \
        zip \
        intl \
    && rm -rf /var/lib/apt/lists/*

RUN printf 'memory_limit=256M\ndisplay_errors=Off\nexpose_php=Off\n' \
        > /usr/local/etc/php/conf.d/zz-app.ini

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

CMD ["tail", "-f", "/dev/null"]
