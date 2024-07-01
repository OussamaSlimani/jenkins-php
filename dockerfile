# Use the official PHP image as the base image
FROM php:8.1-apache

# Set the working directory inside the container
WORKDIR /var/www/html

# Install Composer and required PHP extensions
RUN apt-get update && \
    apt-get install -y unzip && \
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer && \
    docker-php-ext-install mysqli pdo pdo_mysql

# Copy the current directory contents into the container at /var/www/html
COPY src/ .

# Set the working directory to /var/www/html/src
WORKDIR /var/www/html/src

# Install dotenv via Composer in the src folder
RUN composer require vlucas/phpdotenv

# Expose port 80 to the outside world
EXPOSE 80

# Set up environment variables from .env file
COPY .env .env

# Set the working directory back to /var/www/html
WORKDIR /var/www/html

# Ensure the container uses port 80 from the start
CMD ["apache2-foreground"]