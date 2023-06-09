#!/usr/bin/env bash
set -e

export APP_ENV=prod

APP_VERSION=$(git describe --tags)
export APP_VERSION

envsubst < config/parameters-docker.yml.dist > config/parameters.yml

env | grep -v "^_" | while read envvar; do
  name=$(cut -f1 -d= <<<$envvar)
  value=$(cut -f2- -d= <<<$envvar)
  echo "$name='$value'" >> .env.local
done

if ! test -d public/static;then
    mkdir -p public/static
fi

bin/console cache:warmup

chown -R www-data:www-data public/static var/cache var/log

apache2ctl -DFOREGROUND
