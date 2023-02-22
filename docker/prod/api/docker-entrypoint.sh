#!/usr/bin/env bash
set -e

export APP_ENV=prod

APP_VERSION=$(git describe --tags)
export APP_VERSION

envsubst < app/config/parameters-docker.yml.dist > app/config/parameters.yml

composer run-script post-update-cmd

if ! test -d public/static;then
    mkdir -p public/static
fi

chown -R www-data:www-data public/static var/cache var/logs

apache2ctl -DFOREGROUND
