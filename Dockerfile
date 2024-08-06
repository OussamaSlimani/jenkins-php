# Use the official PHP image as the base image
FROM php:apache

# Set the working directory inside the container
WORKDIR /var/www/html

# Install PHP extensions
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Install necessary dependencies
RUN apt-get update && \
    apt-get install -y --no-install-recommends \
        curl \
        git \
        unzip && \
    apt-get clean && rm -rf /var/lib/apt/lists/*

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Copy the current directory contents into the container at /var/www/html
COPY src/ .

# Install PHP dependencies with Composer
RUN composer require promphp/prometheus_client_php

# Expose port 80 for the web application
EXPOSE 80

# Set up environment variables from .env file
COPY src/.env /var/www/html/src/.env

# Add HEALTHCHECK instruction
HEALTHCHECK --interval=30s --timeout=10s --start-period=30s --retries=3 \
  CMD curl -f http://localhost/ || exit 1

# Create a non-root user and group, and set permissions
RUN groupadd -r appgroup && useradd -r -g appgroup -d /home/appuser -s /bin/bash appuser
RUN chown -R appuser:appgroup /var/www/html
USER appuser

# Ensure the container uses port 80 from the start
CMD ["apache2-foreground"]