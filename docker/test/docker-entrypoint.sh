#!/usr/bin/env bash
set -e

case $1 in
  test)
    composer install --no-interaction
    bin/console --env=test cache:clear
    vendor/bin/phpunit
    ;;
  coverage)
    composer install --no-interaction > /dev/null 2>&1
    bin/console --env=test cache:clear > /dev/null 2>&1
    vendor/bin/phpunit --coverage-text 2> /dev/null | grep -A 3 "Summary:" | grep "Lines" | awk '{print $3}' | sed -E 's/\.[0-9]*%//g'
    ;;
  *)
    exec "$@"
    ;;
esac

