#!/usr/bin/env bash

envsubst < web/index.html.dist > web/index.html
envsubst < web/css/style.css.dist > web/index.html
envsubst < public/apidoc.json.dist > public/apidoc.json
envsubst < public/header.md.dist > public/header.md

mkdir -p web/public

apidoc -i public -o web/public

cp -r web/* /var/www/html

nginx -g "daemon off;"

