FROM php:8.3-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    zip \
    unzip \
    sqlite3 \
    libsqlite3-dev \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_sqlite

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Configure Composer to not check security advisories in Docker  
ENV COMPOSER_ALLOW_SUPERUSER=1

# Set working directory
WORKDIR /app

# Copy application (Dockerfile is in api/ folder, so copy current dir)
COPY . /app

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader --ignore-platform-reqs --no-security-blocking --no-scripts

# Create required directories
RUN mkdir -p bootstrap/cache storage/logs && chmod -R 775 bootstrap/cache storage

# Now run post-install scripts that need artisan
RUN php artisan package:discover --ansi

# Generate app key
RUN php artisan key:generate

# Create SQLite database
RUN mkdir -p database && touch database/slotbook.sqlite

# Run migrations
RUN php artisan migrate --force --seed

# Expose port
EXPOSE 8000

# Start Laravel
CMD php artisan serve --host=0.0.0.0 --port=8000
