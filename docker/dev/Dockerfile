FROM debian:buster
ENV DEBIAN_FRONTEND noninteractive
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
    curl \
    wget \
    unzip \
    jq

# Install mongodb server 4.2 for running the tests
RUN apt install -y gnupg
RUN wget -qO - https://www.mongodb.org/static/pgp/server-4.2.asc | apt-key add -
RUN echo "deb http://repo.mongodb.org/apt/debian buster/mongodb-org/4.2 main" > /etc/apt/sources.list.d/mongodb-org-4.2.list
RUN apt update && apt install -y mongodb-org


# Install standard-version
RUN curl -sL https://deb.nodesource.com/setup_12.x | bash -
RUN apt install -y nodejs
RUN npm i -g standard-version

RUN apt clean

# To have composer and npm cache
RUN mkdir /.composer /.npm && chmod -R 777 /.composer /.npm
RUN echo "#!/usr/bin/env bash\n cd /api\n bin/console \"\$@\"\n cd -" > /usr/bin/rec && chmod +x /usr/bin/rec

WORKDIR /api
ENTRYPOINT ["docker/dev/docker-entrypoint.sh"]
CMD ["dev"]

