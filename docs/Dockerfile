FROM debian:bullseye
ENV DEBIAN_FRONTEND noninteractive
RUN apt update && apt install -y curl nginx gnupg gettext-base


RUN curl -sL https://deb.nodesource.com/setup_14.x | bash -
RUN apt update && apt install -y nodejs

RUN npm install -g apidoc

COPY ./ /docs
WORKDIR /docs

RUN chmod +x docker-entrypoint.sh

CMD ["./docker-entrypoint.sh"]

