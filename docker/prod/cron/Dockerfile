FROM debian:stretch
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
    unzip \
    wget \
    python3 \
    python3-pip \
    firefox-esr \
    jq \
    cron


RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
RUN php -r "if (hash_file('sha384', 'composer-setup.php') === '48e3236262b34d30969dca3c37281b3b4bbe3221bda826ac6a9a62d6444cdb0dcd0615698a5cbe587c3f0fe57a54d8f5') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"
RUN php composer-setup.php --install-dir=/usr/bin --filename=composer
RUN php -r "unlink('composer-setup.php');"


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

RUN echo "#!/usr/bin/env bash\n cd /api\n app/console --env=prod \$@\n cd - > /dev/null" > /usr/bin/rec && chmod +x /usr/bin/rec

CMD docker/prod/cron/docker-entrypoint.sh
