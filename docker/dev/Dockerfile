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
    php-xdebug \
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
    gnupg \
    npm

# required to render PDFs
RUN apt update && apt install -y \
    libxrender1 \
    libfontconfig

# Install and configure symfony binary
RUN curl -1sLf 'https://dl.cloudsmith.io/public/symfony/stable/setup.deb.sh' | bash
RUN apt install -y symfony-cli

# Install mongodb server 4.2 for running the tests
RUN wget -qO - https://www.mongodb.org/static/pgp/server-4.2.asc | apt-key add -
RUN echo "deb http://repo.mongodb.org/apt/debian buster/mongodb-org/4.2 main" > /etc/apt/sources.list.d/mongodb-org-4.2.list
RUN apt update && apt install -y \
    mongodb-org \
    mongodb-org-server=4.2.10

# Install standard-version
RUN npm i -g standard-version

RUN apt clean

# To have composer and npm cache
RUN mkdir /.composer /.npm /.cache /.symfony5 && chmod -R 777 /.composer /.npm /.cache /.symfony5
RUN echo "#!/usr/bin/env bash\n cd /api\n bin/console \"\$@\"\n cd -" > /usr/bin/app && chmod +x /usr/bin/app

WORKDIR /api
ENTRYPOINT ["docker/dev/docker-entrypoint.sh"]
CMD ["dev"]

