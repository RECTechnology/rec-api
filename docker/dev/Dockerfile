FROM debian:stretch
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
    firefox-esr \
    jq \
    phpunit

RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
RUN php -r "if (hash_file('sha384', 'composer-setup.php') === '48e3236262b34d30969dca3c37281b3b4bbe3221bda826ac6a9a62d6444cdb0dcd0615698a5cbe587c3f0fe57a54d8f5') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"
RUN php composer-setup.php --install-dir=/usr/bin --filename=composer
RUN php -r "unlink('composer-setup.php');"

# To have composer cache
RUN mkdir /.composer && chmod -R 777 /.composer
RUN echo "#!/usr/bin/env bash\n cd /api\n app/console \$@\n cd -" > /usr/bin/rec && chmod +x /usr/bin/rec

WORKDIR /api
CMD ["app/console", "server:run", "0.0.0.0:8000"]

