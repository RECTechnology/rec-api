#!/usr/bin/env bash

php app/console cache:clear --env=$APP_ENV

apache2ctl -DFOREGROUND

