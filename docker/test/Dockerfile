FROM debian:buster
ENV DEBIAN_FRONTEND noninteractive
RUN apt update && apt install -y \
    php \
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
    libxrender1 \
    composer \
    gettext-base \
    apache2 \
    wget \
    python-pip \
    build-essential \
    libboost-filesystem-dev \
    libboost-program-options-dev \
    libboost-system-dev

# Install mongodb server 4.0 for running the tests
RUN wget https://fastdl.mongodb.org/src/mongodb-src-r4.0.16.tar.gz
RUN tar xzf mongodb-src-r4.0.16.tar.gz
RUN cd mongodb-src-r4.0.16 && cwd && \
    pip install -r buildscripts/requirements.txt && \
    python buildscripts/scons.py mongod && \
    python buildscripts/scons.py --prefix=/usr install

COPY composer.lock /api/composer.lock
COPY composer.json /api/composer.json
WORKDIR /api
RUN composer global require hirak/prestissimo --no-plugins --no-scripts
RUN composer install --no-ansi --no-interaction --no-progress --no-scripts --no-autoloader && rm -rf /root/.composer

COPY . /api

RUN composer dump-autoload --no-ansi --no-interaction --no-scripts --optimize

ENTRYPOINT ["docker/test/docker-entrypoint.sh"]
CMD ["test"]
