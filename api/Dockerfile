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

# Create required directories and set permissions
RUN mkdir -p bootstrap/cache \
    storage/app/public \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/testing \
    storage/framework/views \
    storage/logs \
    && chmod -R 775 bootstrap/cache storage \
    && chown -R www-data:www-data bootstrap/cache storage

# Now run post-install scripts that need artisan
RUN php artisan package:discover --ansi

# Create .env file from .env.example
RUN cp .env.example .env

# Set APP_URL for Render
RUN sed -i "s|APP_URL=.*|APP_URL=https://slotbook-mia7.onrender.com|g" .env

# Generate app key
RUN php artisan key:generate

# Create SQLite database
RUN mkdir -p database && touch database/slotbook.sqlite

# Run migrations only (skip seeding for now - will seed on first run)
RUN php artisan migrate --force

# Create public directory
RUN mkdir -p public

# Expose port for Render
EXPOSE 10000

# Use PHP built-in server pointing to public directory
# Render requires port 10000 for free tier Docker services
# Use shell form to ensure proper signal handling
CMD php -S 0.0.0.0:10000 -t /app/public
