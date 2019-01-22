#!/usr/bin/env bash


export SYMFONY_ENV=prod
composer run-script post-update-cmd

envsubst < app/config/parameters-docker.yml.dist > app/config/parameters.yml

app/console cache:clear --env=$APP_ENV

chown -R www-data:www-data app/cache app/logs

apache2ctl -DFOREGROUND

