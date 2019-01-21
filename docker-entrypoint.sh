#!/usr/bin/env bash

envsubst < app/config/parameters-docker.yml.dist > app/config/parameters.yml
export SYMFONY_ENV=prod
composer run-script post-update-cmd

apache2ctl -DFOREGROUND

