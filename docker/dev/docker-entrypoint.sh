#!/usr/bin/env bash

envsubst < app/config/parameters-docker.yml.dist > app/config/parameters.yml

export SYMFONY_ENV=prod

composer run-script post-update-cmd

chown -R www-data:www-data app/cache app/logs web/static

apache2ctl -DFOREGROUND

