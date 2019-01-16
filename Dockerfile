FROM debian
ENV DEBIAN_FRONTEND noninteractive
RUN apt update && apt install -y php apache2 php-mysql php-xml php-mongodb php-curl php-bcmath php-cli php-mbstring php-zip composer
RUN a2enmod rewrite php7.0
COPY . /api
COPY vhost.conf /etc/apache2/sites-available/000-default.conf
WORKDIR /api
RUN composer.phar install --no-ansi --no-dev --no-interaction --no-progress --no-scripts --optimize-autoloader
CMD ./docker-entrypoint.sh
