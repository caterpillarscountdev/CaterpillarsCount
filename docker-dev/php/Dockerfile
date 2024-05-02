FROM php:7.4-fpm
RUN apt-get update -y && apt-get install -y libjpeg-dev libpng-dev libfreetype6-dev
RUN docker-php-ext-configure gd --with-freetype --with-jpeg
RUN docker-php-ext-install gd
RUN docker-php-ext-install mysqli
