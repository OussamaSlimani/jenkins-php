# Use the official PHP image as the base image
FROM php:8.1-apache

# Set the working directory inside the container
WORKDIR /var/www/html

# Copy the current directory contents into the container at /var/www/html
COPY src/ .

# Expose port 80 to the outside world
EXPOSE 80

# Install necessary PHP extensions for MySQL
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Ensure the container uses the port 80 from the start
CMD ["apache2-foreground"]