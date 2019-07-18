#!/usr/bin/env bash
set -e

export SYMFONY_ENV=prod
export APP_ENV=prod
export HTTPD_USER=www-data

envsubst < app/config/parameters-docker.yml.dist > app/config/parameters.yml

composer run-script post-update-cmd

if ! test -d var/spool;then
    mkdir -p var/spool
fi

if ! test -d web/static;then
    mkdir -p web/static
fi

chown -R $HTTPD_USER:$HTTPD_USER app/cache app/logs web/static var/spool var/cache var/logs

cron -f

