FROM php:8.2-fpm

# Instalar dependencias del sistema
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configurar directorio de trabajo
WORKDIR /var/www/html

# Copiar archivos de configuración
COPY composer.json composer.lock ./

# Instalar dependencias de PHP
RUN composer install --no-scripts --no-autoloader

# Copiar el resto de los archivos
COPY . .

# Completar la instalación de Composer
RUN composer dump-autoload --optimize

# Exponer puerto
EXPOSE 8000

# Comando por defecto
CMD ["php", "-S", "0.0.0.0:8000", "-t", "public"]



