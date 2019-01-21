FROM debian
ENV DEBIAN_FRONTEND noninteractive
RUN apt update && apt install -y php apache2 php-mysql php-xml php-mongodb php-curl php-bcmath php-cli php-mbstring php-zip composer
RUN a2enmod rewrite php7.0
RUN sed 's/^ServerTokens OS/ServerTokens Prod/' -i /etc/apache2/conf-available/security.conf
RUN sed 's/^ServerSignature On/ServerSignature Off/' -i /etc/apache2/conf-available/security.conf
COPY . /api
COPY vhost.conf /etc/apache2/sites-available/000-default.conf
WORKDIR /api
RUN composer install --no-ansi --no-dev --no-interaction --no-progress --no-scripts --optimize-autoloader
CMD ./docker-entrypoint.sh
