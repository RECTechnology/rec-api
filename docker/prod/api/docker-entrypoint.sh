#!/usr/bin/env bash
set -e

export SYMFONY_ENV=prod
export APP_ENV=prod

envsubst < app/config/parameters-docker.yml.dist > app/config/parameters.yml

composer run-script post-update-cmd


chown -R www-data:www-data web/static var/cache var/logs

apache2ctl -DFOREGROUND
