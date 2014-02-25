#!/bin/bash
# export STORAGE_API_TOKEN=YOUR_TOKEN - run before this script

cp parameters.yml.dist.tp parameters.yml.dist
sed "s/SAPI_TOKEN/STORAGE_API_TOKEN/" -i "parameters.yml"

curl -sS https://getcomposer.org/installer | php
php composer.phar install -n
php composer.phar update -n
php composer.phar show --installed

php vendor/bin/phpunit
exit $?
