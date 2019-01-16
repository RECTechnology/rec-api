#!/usr/bin/env bash

php app/console cache:clear

apache2ctl -DFOREGROUND

