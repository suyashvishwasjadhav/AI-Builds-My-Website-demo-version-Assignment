# Stage 1: Build Frontend (React + GSAP + Lenis) and Backend Vite assets
FROM node:20-alpine AS assets

WORKDIR /app

# Build React intake frontend -> outputs to /app/backend/public/landing
COPY frontend/package*.json ./frontend/
RUN cd frontend && npm ci
COPY frontend ./frontend
RUN mkdir -p backend/public && cd frontend && npm run build

# Build Laravel Blade/Tailwind/GSAP assets -> outputs to /app/backend/public/build
COPY backend/package*.json ./backend/
RUN cd backend && npm ci
COPY backend/vite.config.js backend/tailwind.config.js backend/postcss.config.js ./backend/
COPY backend/resources ./backend/resources
RUN cd backend && npm run build

# Stage 2: PHP Runtime + Composer + App
FROM php:8.4-cli-alpine

COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions pdo_mysql pdo_sqlite mbstring xml zip gd bcmath pcntl

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# Copy shared database directory and backend application
COPY database ./database
COPY backend ./backend
COPY --from=assets /app/backend/public/landing ./backend/public/landing
COPY --from=assets /app/backend/public/build ./backend/public/build

RUN cd backend \
    && composer install --no-dev --optimize-autoloader --no-interaction \
    && mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs storage/app/public \
    && chmod -R 775 storage bootstrap/cache

COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 8000

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
