#!/usr/bin/env bash
set -e

case $1 in
  dev)
    symfony local:server:start --no-tls --allow-http --port=8000
    ;;
  test)
    SYMFONY_DEPRECATIONS_HELPER=disabled vendor/bin/phpunit
    ;;
  coverage)
    XDEBUG_MODE=coverage vendor/bin/phpunit -d memory_limit=1G --coverage-clover coverage.xml --do-not-cache-result --process-isolation
    ;;
  *)
    exec "$@"
    ;;
esac

