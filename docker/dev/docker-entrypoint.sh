#!/usr/bin/env bash
set -e

case $1 in
  dev)
    bin/console server:run 0.0.0.0:8000
    ;;
  test)
    vendor/bin/phpunit
    ;;
  coverage)
    apt update && apt install -y php-xdebug
    XDEBUG_MODE=coverage vendor/bin/phpunit -d memory_limit=1G --coverage-clover coverage.xml --do-not-cache-result
    ;;
  *)
    exec "$@"
    ;;
esac

