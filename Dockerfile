# Base image: PHP 8.3-alpine
FROM php:8.3-alpine 

# install system dependencies
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
    libxml2-dev

#install composer (PHP package manager)
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Install PHP extensions for MySQL
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd pdo pdo_mysql mysqli zip

# check if mbstring extension is installed (for debugging purposes)
RUN php -m | grep mbstring

# set working directory
WORKDIR /app

# copy application file into the container
COPY . /app

# install dependencies
RUN composer install --optimize-autoloader --no-dev

# Generate Laravel application key
RUN php artisan key:generate

# Command to run the laravel development server
CMD php artisan serve --host=0.0.0.0 --port=8000

# Expose port 8000
EXPOSE 8000