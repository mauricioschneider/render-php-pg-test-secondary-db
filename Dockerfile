FROM richarvey/nginx-php-fpm:3.1.6

# Set working directory
WORKDIR /var/www/html

# --- 1. Install Dependencies ---

# Install required system libraries for PostgreSQL (libpq-dev)
RUN apt-get update && apt-get install -y \
    libpq-dev \
    && rm -rf /var/lib/apt/lists/*

# Install pdo_pgsql extension (CRITICAL: Required by your script)
RUN docker-php-ext-install pdo_pgsql

# --- 2. Copy and Install Composer Dependencies ---

# Copy application files (source code, composer files, etc.)
COPY . .

# Install Composer dependencies (CRITICAL: Creates the 'vendor' directory)
RUN composer install --no-dev --optimize-autoloader

# --- 3. Image Configuration ---

# Set the document root to your public folder
ENV WEBROOT /var/www/html/public

# Allow composer to run as root during the build step
ENV COMPOSER_ALLOW_SUPERUSER 1

# Start the web server and PHP-FPM
CMD ["/start.sh"]