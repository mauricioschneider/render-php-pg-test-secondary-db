# --- STAGE 1: Build Stage (Install Dependencies and Compile) ---
FROM php:8.4.1-cli-alpine AS composer_install

WORKDIR /app

# 1. Install required SYSTEM dependencies (libpq-dev for headers/compilation)
RUN apt-get update && apt-get install -y \
    libpq-dev \
    && rm -rf /var/lib/apt/lists/*

# 2. Install the pdo_pgsql extension
# This step relies on libpq-dev being present
RUN docker-php-ext-install pdo_pgsql

# Install Composer
COPY --from=composer/composer:latest /usr/bin/composer /usr/bin/composer

# Copy necessary files and install Composer dependencies
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader

# --- STAGE 2: Production Stage (Runtime Environment) ---
# We switch to an Alpine image for a smaller footprint.
FROM php:8.4.1-cli-alpine

WORKDIR /app

# 1. Install required RUNTIME dependencies on Alpine (libpq)
# This package provides the necessary shared library files (.so)
RUN apk add --no-cache libpq

# 2. Re-install pdo_pgsql using the Alpine-specific base
# This ensures the extension is compiled and linked against the Alpine libpq.
# NOTE: This step is crucial because the previous stage's extension is linked against Debian's libs.
RUN docker-php-ext-install pdo_pgsql

# Copy application files (source code)
COPY . .

# Copy installed dependencies from the build stage
COPY --from=composer_install /app/vendor /app/vendor

# Set the execution command
CMD ["composer", "start"]