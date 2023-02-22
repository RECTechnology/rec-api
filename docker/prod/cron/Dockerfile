FROM debian:bullseye
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
    wget \
    python3 \
    python3-pip \
    jq \
    cron \
    git \
    procps \
    ifstat

COPY docker/prod/cron/crontab /etc/cron.d/app
RUN chmod 644 /etc/cron.d/app

WORKDIR /api
COPY . /api

RUN composer install --no-dev --no-interaction --no-scripts

RUN echo "#!/usr/bin/env bash\n cd /api\n bin/console --env=prod \"\$@\"\n cd - > /dev/null" > /usr/bin/app && chmod +x /usr/bin/app

CMD docker/prod/cron/docker-entrypoint.sh
