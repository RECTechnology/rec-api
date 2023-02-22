#!/usr/bin/env bash
set -e

export APP_ENV=prod

APP_VERSION=$(git describe --tags)
export APP_VERSION

envsubst < config/parameters-docker.yml.dist > config/parameters.yml

composer run-script post-update-cmd

if ! test -d public/static;then
    mkdir -p public/static
fi

chown -R www-data:www-data public/static var/cache var/logs

cron -f

