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
    unzip \
    wget \
    python-pip \
    composer \
    jq \
    phpunit \
    php-xdebug \
    curl \
    build-essential \
    libboost-filesystem-dev \
    libboost-program-options-dev \
    libboost-system-dev

# Install mongodb server 4.0 for running the tests
RUN wget https://fastdl.mongodb.org/src/mongodb-src-r4.0.16.tar.gz
RUN tar xzf mongodb-src-r4.0.16.tar.gz
WORKDIR mongodb-src-r4.0.16
RUN pip install -r buildscripts/requirements.txt
RUN python buildscripts/scons.py mongod
RUN python buildscripts/scons.py --prefix=/usr install


# Install standard-version
WORKDIR /
RUN curl -sL https://deb.nodesource.com/setup_12.x | bash -
RUN apt install -y nodejs
RUN npm i -g standard-version

# To have composer and npm cache
RUN mkdir /.composer /.npm && chmod -R 777 /.composer /.npm
RUN echo "#!/usr/bin/env bash\n cd /api\n bin/console \"\$@\"\n cd -" > /usr/bin/rec && chmod +x /usr/bin/rec

WORKDIR /api
ENTRYPOINT ["docker/dev/docker-entrypoint.sh"]
CMD ["dev"]

