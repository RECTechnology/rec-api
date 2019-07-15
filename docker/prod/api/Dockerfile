FROM debian:stretch
ENV DEBIAN_FRONTEND noninteractive
RUN apt update && apt install -y \
    php \
    apache2 \
    php-mysql \
    php-xml \
    php-mongodb \
    php-curl \
    php-bcmath \
    php-cli \
    php-mbstring \
    php-zip \
    php-redis \
    unzip \
    gettext-base \
    wget

RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
RUN php -r "if (hash_file('sha384', 'composer-setup.php') === '48e3236262b34d30969dca3c37281b3b4bbe3221bda826ac6a9a62d6444cdb0dcd0615698a5cbe587c3f0fe57a54d8f5') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"
RUN php composer-setup.php --install-dir=/usr/bin --filename=composer
RUN php -r "unlink('composer-setup.php');"

COPY docker/prod/api/vhost.conf /etc/apache2/sites-available/000-default.conf
RUN a2enmod rewrite php7.0
RUN sed 's/^ServerTokens OS/ServerTokens Prod/' -i /etc/apache2/conf-available/security.conf
RUN sed 's/^ServerSignature On/ServerSignature Off/' -i /etc/apache2/conf-available/security.conf

COPY composer.lock /api/composer.lock
COPY composer.json /api/composer.json
WORKDIR /api
RUN composer global require hirak/prestissimo --no-plugins --no-scripts
RUN composer install --no-ansi --no-dev --no-interaction --no-progress --no-scripts --no-autoloader && rm -rf /root/.composer

COPY . /api

RUN composer dump-autoload --no-ansi --no-interaction --no-scripts --no-dev --optimize

CMD docker/prod/api/docker-entrypoint.sh
