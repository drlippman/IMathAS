# Use official PHP 8.1 with Apache
FROM php:8.1-apache

# Set working directory
WORKDIR /var/www/html

# Install system dependencies
RUN apt-get update && apt-get install -y \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    libzip-dev \
    libxml2-dev \
    libonig-dev \
    libcurl4-openssl-dev \
    gettext \
    unzip \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions required by IMathAS
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
    gd \
    pdo_mysql \
    mysqli \
    zip \
    mbstring \
    xml \
    curl \
    gettext

# Enable Apache modules
RUN a2enmod rewrite headers

# Copy IMathAS source code
COPY . /var/www/html/

# Create required directories and set permissions
RUN mkdir -p /var/www/html/filestore \
    && mkdir -p /var/www/html/assessment/qimages \
    && mkdir -p /var/www/html/filter/graph/imgs \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 777 /var/www/html/filestore \
    && chmod -R 777 /var/www/html/assessment/qimages \
    && chmod -R 777 /var/www/html/filter/graph/imgs

# Copy Apache configuration
COPY docker/apache-config.conf /etc/apache2/sites-available/000-default.conf

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader

# Expose port 8080 (Cloud Run requirement)
EXPOSE 8080

# Configure Apache to listen on port 8080
RUN sed -i 's/80/8080/g' /etc/apache2/ports.conf /etc/apache2/sites-available/000-default.conf

# Start Apache
CMD ["apache2-foreground"]