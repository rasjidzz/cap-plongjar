FROM php:8.2-fpm-alpine

RUN apk update && apk add --no-cache \
    curl \
    git \
    build-base \
    libxml2-dev \
    libzip-dev \
    postgresql-dev \
    sqlite-dev \
    mysql-client \
    autoconf \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    && rm -rf /var/cache/apk/* \
    && echo "✅ System dependencies installed"

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && echo "✅ Composer installed"

RUN docker-php-ext-install -j$(nproc) \
    pdo_mysql \
    zip \
    exif \
    pcntl \
    bcmath \
    gd \
    && echo "✅ PHP extensions installed"

RUN docker-php-ext-enable opcache \
    && { \
        echo 'opcache.enable=1'; \
        echo 'opcache.revalidate_freq=0'; \
        echo 'opcache.validate_timestamps=0'; \
        echo 'opcache.max_accelerated_files=10000'; \
        echo 'opcache.memory_consumption=128'; \
        echo 'opcache.interned_strings_buffer=8'; \
        echo 'opcache.fast_shutdown=1'; \
        echo 'opcache.enable_cli=1'; \
    } | tee /usr/local/etc/php/conf.d/opcache-recommended.ini \
    && echo "✅ OPcache configured and enabled"

WORKDIR /var/www/html

COPY composer.json composer.lock ./

RUN composer install --no-dev --optimize-autoloader --no-scripts --prefer-dist \
    && echo "✅ Composer dependencies installed"

COPY . .

RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache \
    && echo "✅ Permissions set for storage and bootstrap/cache"

EXPOSE 9000

CMD ["php-fpm"]
