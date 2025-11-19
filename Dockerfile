# --- STAGE 1: Build Stage (Install Dependencies) ---
# We use a base image with the required PHP version and extensions
FROM php:8.3-cli AS composer_install

# Set working directory for the application code
WORKDIR /app

# Install required system dependencies for pgsql
RUN apt-get update && apt-get install -y \
    libpq-dev \
    && rm -rf /var/lib/apt/lists/*

# Install the pdo_pgsql extension
RUN docker-php-ext-install pdo_pgsql

# Install Composer
COPY --from=composer/composer:latest /usr/bin/composer /usr/bin/composer

# Copy only the necessary files for dependency resolution
COPY composer.json composer.lock ./

# Install project dependencies
# The --no-dev flag keeps the final image smaller
RUN composer install --no-dev --optimize-autoloader

# --- STAGE 2: Production Stage (Runtime Environment) ---
# Use a minimal production base image (CLI since it's a script, not a web server)
FROM php:8.4.1-cli-alpine

# Set working directory
WORKDIR /app

# Install required system dependencies for pgsql on Alpine (musl libc)
RUN apk add --no-cache libpq

# Install the pdo_pgsql extension
# The command below uses the PHP extension installer tailored for Alpine/Docker
RUN docker-php-ext-install pdo_pgsql

# Copy application files (source code)
COPY . .

# Copy installed dependencies from the build stage
COPY --from=composer_install /app/vendor /app/vendor

# Use CMD to define the default command that Render will execute.
# This runs your script using the Composer 'start' alias.
CMD ["composer", "start"]