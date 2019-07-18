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
    unzip \
    wget \
    python3 \
    python3-pip \
    composer \
    jq \
    phpunit

# To have composer cache
RUN mkdir /.composer && chmod -R 777 /.composer
RUN echo "#!/usr/bin/env bash\n cd /api\n bin/console \$@\n cd -" > /usr/bin/rec && chmod +x /usr/bin/rec

WORKDIR /api
CMD ["bin/console", "server:run", "0.0.0.0:8000"]

