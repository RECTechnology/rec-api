#!/usr/bin/env bash
set -e

case $1 in
  test)
    composer install --no-interaction --no-scripts
    bin/console --env=test cache:clear
    vendor/bin/phpunit
    ;;
  *)
    exec "$@"
    ;;
esac

