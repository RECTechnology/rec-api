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
    libxrender1 \
    unzip \
    wget \
    python3 \
    python3-pip \
    composer \
    jq \
    phpunit \
    php-xdebug \
    curl

RUN curl -sL https://deb.nodesource.com/setup_12.x | bash -
RUN apt install -y nodejs
RUN npm i -g standard-version

# To have composer and npm cache
RUN mkdir /.composer /.npm && chmod -R 777 /.composer /.npm
RUN echo "#!/usr/bin/env bash\n cd /api\n bin/console \"\$@\"\n cd -" > /usr/bin/rec && chmod +x /usr/bin/rec

WORKDIR /api
ENTRYPOINT ["docker/dev/docker-entrypoint.sh"]
CMD ["dev"]

