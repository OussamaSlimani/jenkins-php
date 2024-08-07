# Use the official PHP image as the base image
FROM php:apache

# Set the working directory inside the container
WORKDIR /var/www/html

# Install PHP extensions
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Install only necessary dependencies
RUN apt-get update && \
    apt-get install -y --no-install-recommends curl wget && \
    apt-get clean && rm -rf /var/lib/apt/lists/*

# Install Prometheus Apache Exporter
RUN wget https://github.com/Lusitaniae/apache_exporter/releases/download/v0.10.1/apache_exporter-0.10.1.linux-amd64.tar.gz && \
    tar -xzf apache_exporter-0.10.1.linux-amd64.tar.gz && \
    mv apache_exporter-0.10.1.linux-amd64/apache_exporter /usr/local/bin/ && \
    rm -rf apache_exporter-0.10.1.linux-amd64*

# Copy the current directory contents into the container at /var/www/html
COPY src/ .

# Expose port 80 for the web application and port 9117 for the exporter
EXPOSE 80
EXPOSE 9117

# Set up environment variables from .env file
COPY src/.env /var/www/html/src/.env

# Add HEALTHCHECK instruction
HEALTHCHECK --interval=30s --timeout=10s --start-period=30s --retries=3 \
  CMD curl -f http://localhost/ || exit 1

# Create a non-root user and group, and set permissions
RUN groupadd -r appgroup && useradd -r -g appgroup -d /home/appuser -s /bin/bash appuser
RUN chown -R appuser:appgroup /var/www/html
USER appuser

# Start Apache and Apache Exporter
CMD ["sh", "-c", "apache2-foreground & apache_exporter --telemetry.address=:9117 --telemetry.endpoint=/metrics"]
