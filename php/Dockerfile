FROM php:8.3-apache

# Extensions utiles (pdo_mysql pour MySQL)
RUN docker-php-ext-install pdo pdo_mysql

# RUN apk add --no-cache \
#     unzip \
#     libzip-dev \
#     && docker-php-ext-install zip

RUN a2enmod rewrite

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer