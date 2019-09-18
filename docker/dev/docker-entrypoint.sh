#!/usr/bin/env bash
set -e

install_deps(){
    composer install --no-interaction
}

case $1 in
  dev)
    install_deps
    bin/console server:run 0.0.0.0:8000
    ;;
  test)
    install_deps
    if test -f var/db/test.sqlite;then
      rm var/db/test.sqlite
    fi
    vendor/bin/phpunit
    ;;
  *)
    exec "$@"
    ;;
esac

