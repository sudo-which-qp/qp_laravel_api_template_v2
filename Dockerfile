# Base image: PHP 8.3-alpine
FROM php:8.3-alpine 

# Install system dependencies
RUN apk update && apk add --no-cache \
    openssl \
    zip \
    unzip \
    git \
    mysql-client \
    libzip-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libxml2-dev \
    curl \
    bash

# Install composer (PHP package manager)
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Install PHP extensions for MySQL
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd pdo pdo_mysql mysqli zip

# Set working directory
WORKDIR /app

# Copy composer files first for better caching
COPY composer.json composer.lock ./

# Set up entrypoint script
COPY docker-entrypoint.sh /docker-entrypoint.sh
RUN chmod +x /docker-entrypoint.sh

# Copy the rest of the application
COPY . .

# Expose port 8000
EXPOSE 8000

# Use entrypoint script to handle setup tasks
ENTRYPOINT ["/docker-entrypoint.sh"]

# Default command
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]