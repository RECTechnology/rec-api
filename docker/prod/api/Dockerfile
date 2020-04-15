FROM debian:buster
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
RUN a2enmod rewrite php7.3
RUN sed 's/^ServerTokens OS/ServerTokens Prod/' -i /etc/apache2/conf-available/security.conf
RUN sed 's/^ServerSignature On/ServerSignature Off/' -i /etc/apache2/conf-available/security.conf
RUN sed 's/^memory_limit = 128M$/memory_limit = 1G/g' -i /etc/php/7.3/apache2/php.ini

COPY composer.lock /api/composer.lock
COPY composer.json /api/composer.json
WORKDIR /api
RUN composer global require hirak/prestissimo --no-plugins --no-scripts
RUN composer install --no-ansi --no-dev --no-interaction --no-progress --no-scripts --no-autoloader && rm -rf /root/.composer

COPY . /api

RUN composer dump-autoload --no-ansi --no-interaction --no-scripts --no-dev --optimize

CMD docker/prod/api/docker-entrypoint.sh
