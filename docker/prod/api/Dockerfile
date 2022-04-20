FROM debian:bullseye
ENV DEBIAN_FRONTEND noninteractive
RUN apt update && apt install -y \
    php \
    php-mysql \
    php-xml \
    php-mongodb \
    php-curl \
    php-bcmath \
    php-cli \
    php-mbstring \
    php-zip \
    php-redis \
    php-gd \
    libxrender1 \
    composer \
    gettext-base \
    apache2 \
    wget \
    git

COPY docker/prod/api/vhost.conf /etc/apache2/sites-available/000-default.conf
RUN a2enmod rewrite php7.4
RUN sed 's/^ServerTokens OS/ServerTokens Prod/' -i /etc/apache2/conf-available/security.conf
RUN sed 's/^ServerSignature On/ServerSignature Off/' -i /etc/apache2/conf-available/security.conf
RUN sed 's/^memory_limit = 128M$/memory_limit = 1G/g' -i /etc/php/7.4/apache2/php.ini
RUN sed 's/^post_max_size = 8M$/post_max_size = 20M/g' -i /etc/php/7.4/apache2/php.ini
RUN sed 's/^upload_max_filesize = 2M$/upload_max_filesize = 20M/g' -i /etc/php/7.4/apache2/php.ini

WORKDIR /api
COPY . /api

RUN composer install --no-dev --no-interaction

CMD docker/prod/api/docker-entrypoint.sh
