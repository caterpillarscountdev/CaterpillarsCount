FROM php:8.4-fpm
RUN apt-get update -y && apt-get install -y libjpeg-dev libpng-dev libfreetype6-dev libzip-dev
RUN docker-php-ext-configure gd --with-freetype --with-jpeg
RUN docker-php-ext-install gd
RUN docker-php-ext-install mysqli
RUN docker-php-ext-install zip

RUN addgroup --gid 127 mygroup && adduser --disabled-password --gecos "" --force-badname --ingroup mygroup myuser
USER myuser
