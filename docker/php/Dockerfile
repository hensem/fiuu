# ===============================================
# 🧩 PHP + Composer + Node.js for Laravel + Vite
# ===============================================
FROM php:8.2-fpm

# --- Install system dependencies and PHP extensions ---
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    zip \
    curl \
    gnupg \
    ca-certificates \
    bash \
    && docker-php-ext-install pdo pdo_mysql

# --- Install Composer globally ---
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# --- Install Node.js (v20 LTS) ---
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && npm install -g npm@latest

# --- Disable OPcache in dev mode (optional) ---
RUN echo "opcache.enable=0" > /usr/local/etc/php/conf.d/opcache-dev.ini \
 && echo "opcache.validate_timestamps=1" >> /usr/local/etc/php/conf.d/opcache-dev.ini \
 && echo "opcache.revalidate_freq=0" >> /usr/local/etc/php/conf.d/opcache-dev.ini

# --- Set working directory ---
WORKDIR /var/www/html

# --- Copy app source code ---
COPY . /var/www/html

# --- Default command ---
CMD ["php-fpm"]
