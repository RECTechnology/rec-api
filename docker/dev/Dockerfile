FROM debian
ENV DEBIAN_FRONTEND noninteractive
RUN apt update && apt install -y \
    php-mysql \
    php-xml \
    php-mongodb \
    php-curl \
    php-bcmath \
    php-cli \
    php-mbstring \
    php-zip \
    composer \
    wget \
    python3 \
    python3-pip \
    firefox-esr \
    jq


# To have composer cache
RUN mkdir /.composer && chmod -R 777 /.composer
RUN echo "#!/usr/bin/env bash\n cd /api\n app/console \$@\n cd -" > /usr/bin/rec && chmod +x /usr/bin/rec

WORKDIR /api
CMD ["app/console", "server:run", "0.0.0.0:8000"]

