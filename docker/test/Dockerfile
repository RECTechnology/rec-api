FROM debian:buster
ENV DEBIAN_FRONTEND noninteractive
# PHP and deps
RUN apt update && apt install -y \
    php-mysql \
    php-sqlite3 \
    php-xml \
    php-mongodb \
    php-curl \
    php-bcmath \
    php-cli \
    php-mbstring \
    php-zip \
    php-redis \
    php-gd \
    php-xdebug \
    composer \
    phpunit

# System utils
RUN apt update && apt install -y \
    curl \
    wget \
    unzip \
    jq \
    gnupg

# required to render PDFs
RUN apt update && apt install -y \
    libxrender1 \
    libfontconfig

# Install mongodb server 4.2 for running the tests
RUN wget -qO - https://www.mongodb.org/static/pgp/server-4.2.asc | apt-key add -
RUN echo "deb http://repo.mongodb.org/apt/debian buster/mongodb-org/4.2 main" > /etc/apt/sources.list.d/mongodb-org-4.2.list
RUN apt update && apt install -y \
    mongodb-org

COPY composer.lock /api/composer.lock
COPY composer.json /api/composer.json
WORKDIR /api
RUN composer global require hirak/prestissimo --no-plugins --no-scripts
RUN composer install --no-ansi --no-interaction --no-progress --no-scripts --no-autoloader && rm -rf /root/.composer

COPY . /api

RUN composer dump-autoload --no-ansi --no-interaction --no-scripts --optimize

ENTRYPOINT ["docker/test/docker-entrypoint.sh"]
CMD ["test"]
