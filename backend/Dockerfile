FROM php:8.4-cli-alpine

# Install system dependencies and PHP extensions
RUN apk add --no-cache \
    postgresql-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    zip \
    libzip-dev \
    icu-dev \
    oniguruma-dev \
    linux-headers \
    $PHPIZE_DEPS \
 && docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install -j$(nproc) \
    pdo_pgsql \
    pgsql \
    gd \
    zip \
    intl \
    mbstring \
    opcache \
 && apk del $PHPIZE_DEPS linux-headers

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy composer files first for layer caching
COPY composer.json composer.lock* ./
RUN composer install --no-interaction --optimize-autoloader --no-dev || \
    composer install --no-interaction --optimize-autoloader

# Copy the rest of the backend code
COPY . .

# Create writable directories
RUN mkdir -p uploads/images uploads/documents logs \
 && chown -R www-data:www-data uploads logs

# Copy OPcache config
COPY config/opcache.ini /usr/local/etc/php/conf.d/99-opcache.ini

# Expose port and start PHP built-in server
EXPOSE 8000

CMD ["php", "-S", "0.0.0.0:8000", "-t", "public"]
