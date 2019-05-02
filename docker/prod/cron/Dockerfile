FROM debian
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
    gettext-base \
    composer \
    wget \
    python3 \
    python3-pip \
    firefox-esr \
    jq \
    cron

# INSTALL GECKODRIVER
RUN wget https://github.com/mozilla/geckodriver/releases/download/v0.24.0/geckodriver-v0.24.0-linux64.tar.gz -O geckodriver.tgz
RUN tar xzf geckodriver.tgz && mv geckodriver /usr/local/bin/geckodriver && rm geckodriver.tgz

# INSTALL SELENIUM
RUN pip3 install selenium

COPY docker/prod/cron/crontab /etc/cron.d/app
RUN chmod 644 /etc/cron.d/app

COPY composer.lock /api/composer.lock
COPY composer.json /api/composer.json
WORKDIR /api
RUN composer global require hirak/prestissimo --no-plugins --no-scripts
RUN composer install --no-ansi --no-dev --no-interaction --no-progress --no-scripts --no-autoloader && rm -rf /root/.composer

COPY . /api

RUN composer dump-autoload --no-ansi --no-interaction --no-scripts --no-dev --optimize

CMD docker/prod/cron/docker-entrypoint.sh
