#!/usr/bin/env bash
set -e

case $1 in
  dev)
    composer install --no-interaction
    bin/console server:run 0.0.0.0:8000
    ;;
  *)
    exec "$@"
    ;;
esac

