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

app cache:warmup

cron -f

