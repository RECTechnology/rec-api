FROM debian:bullseye
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
    composer \
    phpunit \
    procps \
    ifstat

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
    mongodb-org \
    mongodb-org-server=4.2.10

WORKDIR /api
COPY . /api

RUN composer install --no-interaction

ENTRYPOINT ["docker/dev/docker-entrypoint.sh"]
CMD ["test"]
