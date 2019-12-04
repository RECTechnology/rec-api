#!/usr/bin/env bash
set -e

case $1 in
  test)
    composer install --no-interaction
    bin/console --env=test cache:clear
    vendor/bin/phpunit
    ;;
  coverage)
    composer install --no-interaction
    bin/console --env=test cache:clear
    vendor/bin/phpunit --coverage-text
    ;;
  *)
    exec "$@"
    ;;
esac

