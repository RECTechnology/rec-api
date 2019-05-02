FROM debian
ENV DEBIAN_FRONTEND noninteractive
RUN apt update && apt install -y php-mysql php-xml php-mongodb php-curl php-bcmath php-cli php-mbstring php-zip composer
WORKDIR /api
CMD app/console server:run 0.0.0.0:8000

