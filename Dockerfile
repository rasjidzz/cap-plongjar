# Gunakan image PHP-FPM resmi sebagai base image
FROM php:8.2-fpm-alpine

# Tahap 1: Instal dependensi sistem yang dibutuhkan
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
    # Tambahkan dependensi untuk GD:
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    # imagemagick-dev # Biarkan dikomentari dulu jika belum yakin perlu
    && rm -rf /var/cache/apk/* \
    && echo "✅ System dependencies installed"

# Tahap 2: Download dan instal Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && echo "✅ Composer installed"

# Tahap 3: Instal ekstensi PHP yang dibutuhkan
RUN docker-php-ext-install -j$(nproc) \
    pdo_mysql \
    zip \
    exif \
    pcntl \
    bcmath \
    gd \
    # Tambahkan ekstensi tambahan yang mungkin dibutuhkan GD, misal 'jpeg' jika belum ada
    # docker-php-ext-configure gd --with-freetype --with-jpeg \
    && echo "✅ PHP extensions installed"

# Tahap 4: Aktifkan OPcache (penting untuk performa)
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

# Opsional: Instal Xdebug untuk pengembangan lokal (hanya jika Anda membutuhkannya)
# Anda bisa menghapus bagian ini untuk image produksi agar lebih ringan.
# RUN pecl install xdebug \
#     && docker-php-ext-enable xdebug \
#     && { \
#         echo 'xdebug.mode=develop,debug'; \
#         echo 'xdebug.start_with_request=yes'; \
#         echo 'xdebug.client_host=host.docker.internal'; \
#         echo 'xdebug.client_port=9003'; \
#         echo 'xdebug.idekey=VSCODE'; \
#     } | tee /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
#     && echo "✅ Xdebug installed and configured"


# Set direktori kerja di dalam kontainer
WORKDIR /var/www/html

# 1. Salin composer.json dan composer.lock terlebih dahulu
# Ini memanfaatkan cache Docker. Jika kedua file ini tidak berubah,
# langkah 'composer install' tidak akan dijalankan ulang (hemat waktu).
COPY composer.json composer.lock ./

# 2. Instal dependensi Composer
# Ini adalah langkah yang membuat folder 'vendor'
RUN composer install --no-dev --optimize-autoloader --no-scripts --prefer-dist \
    && echo "✅ Composer dependencies installed"

# 3. Salin seluruh isi proyek Laravel
# Ini akan menimpa composer.json/lock yang sudah disalin, tapi itu tidak masalah.
# Yang penting, folder 'vendor' yang sudah dibuat di langkah sebelumnya akan tetap ada.
COPY . .

# Berikan izin ke direktori storage dan bootstrap/cache
RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache \
    && echo "✅ Permissions set for storage and bootstrap/cache"

# Expose port PHP-FPM (default 9000)
EXPOSE 9000

# Perintah default saat kontainer dijalankan
CMD ["php-fpm"]
