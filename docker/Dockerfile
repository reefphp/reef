FROM php:7.2-apache

# Install git
RUN apt-get update && apt-get install -y libzip-dev unzip nano git libmcrypt-dev libpq-dev libcurl4-gnutls-dev libicu-dev libvpx-dev libjpeg-dev libpng-dev libxpm-dev zlib1g-dev libfreetype6-dev libxml2-dev libexpat1-dev libbz2-dev libgmp3-dev libldap2-dev unixodbc-dev libsqlite3-dev libaspell-dev libsnmp-dev libpcre3-dev libtidy-dev
RUN docker-php-ext-install pdo mbstring pdo_mysql pdo_pgsql curl json intl gd xml zip bz2 opcache
ENV IN_DOCKER 1

# Install Composer
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
RUN php composer-setup.php --install-dir=. --filename=composer
RUN mv composer /usr/local/bin/

EXPOSE 80
