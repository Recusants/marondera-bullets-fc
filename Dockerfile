FROM php:8.2-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    zip \
    unzip \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install mysqli pdo pdo_mysql mbstring exif pcntl bcmath gd

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Configure Apache to use index.php as default
RUN echo "DirectoryIndex index.php" > /etc/apache2/conf-available/directory-index.conf && \
    a2enconf directory-index

# Set ServerName to suppress warning
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Copy all files to Apache document root
COPY . /var/www/html/

# Set permissions
RUN chown -R www-data:www-data /var/www/html && \
    chmod -R 755 /var/www/html

# IMPORTANT: Enable environment variable passthrough for Apache
RUN echo "PassEnv DB_HOST" >> /etc/apache2/conf-available/environment.conf && \
    echo "PassEnv DB_USER" >> /etc/apache2/conf-available/environment.conf && \
    echo "PassEnv DB_PASS" >> /etc/apache2/conf-available/environment.conf && \
    echo "PassEnv DB_NAME" >> /etc/apache2/conf-available/environment.conf && \
    a2enconf environment

# Expose port 80
EXPOSE 80

# Start Apache in foreground
CMD ["apache2-foreground"]