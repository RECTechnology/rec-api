#!/bin/bash

cat <<EOF
*** Telepay Installer started ***
EOF

if [[ "$SYMFONY_ENV" != "prod" ]];then
    cat <<EOF
Warning, this script must not be used you are not sure because it will override the parameters.yml file.
If you want to execute this script please export the environment var SYMFONY_ENV as 'prod'.
*** Installer finished failed ***
EOF
    exit -1
fi

if [[ -t 0 ]];then
    cat <<EOF
You must provide the content of parameters.yml from stdin
*** Installer finished failed ***
EOF
    exit -2
fi

cat > app/config/parameters.yml

curl -sS https://getcomposer.org/installer -s | php
php composer.phar install --no-scripts --no-dev --optimize-autoloader --quiet
php vendor/sensio/distribution-bundle/Sensio/Bundle/DistributionBundle/Resources/bin/build_bootstrap.php

php app/console doctrine:schema:update --force --env=prod
php app/console doctrine:schema:update --force --env=sandbox

php app/console doctrine:mongodb:schema:create --env=prod
php app/console doctrine:mongodb:schema:create --env=sandbox

php app/console cache:clear --env=prod
php app/console cache:clear --env=sandbox

cat <<EOF
*** Installer finished success ***
EOF
exit 0