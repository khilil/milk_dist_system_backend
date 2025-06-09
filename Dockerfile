FROM php:8.1-apache

# Install MySQLi extension for DB connection
RUN docker-php-ext-install mysqli && a2enmod rewrite

# Copy all project files to Apache web root
COPY . /var/www/html/

# Set correct permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Expose port 80 for web traffic
EXPOSE 80
