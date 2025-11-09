FROM php:8.2-fpm

# Install dependencies
RUN docker-php-ext-install pdo pdo_mysql

# Disable PHP OPcache in development
RUN echo "opcache.enable=0" > /usr/local/etc/php/conf.d/opcache-dev.ini \
 && echo "opcache.validate_timestamps=1" >> /usr/local/etc/php/conf.d/opcache-dev.ini \
 && echo "opcache.revalidate_freq=0" >> /usr/local/etc/php/conf.d/opcache-dev.ini


RUN apt-get update && apt-get install -y \
    curl \
    gnupg \
    ca-certificates \
    bash

# Add NodeSource repository & install Node.js v20 (LTS)
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && npm install -g npm@latest

# Clean up
RUN apt-get clean && rm -rf /var/lib/apt/lists/*


WORKDIR /var/www/html

# Copy existing application folder contents
COPY . /var/www/html

# No chmod/chown (Windows-safe)
CMD php-fpm
