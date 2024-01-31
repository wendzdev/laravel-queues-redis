# Use the official PHP 8.1 FPM image as the base image
FROM php:8.1-fpm

# Set the working directory
WORKDIR /var/www/html

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    zip \
    unzip \
    libzip-dev

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql zip

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Install Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Install additional Composer packages
RUN composer require predis/predis symfony/dom-crawler guzzlehttp/guzzle

# Copy the application files into the container
COPY . /var/www/html

# Run Composer autoload and optimize
RUN composer dump-autoload --optimize

# Set up the script to connect to Redis
COPY entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/entrypoint.sh

# Start the script when the container runs
CMD ["/usr/local/bin/entrypoint.sh"]
