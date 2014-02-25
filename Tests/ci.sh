#!/bin/bash
# export STORAGE_API_TOKEN=YOUR_TOKEN - run before this script

cp parameters.yml.dist.tp parameters.yml.dist
sed "s/SAPI_TOKEN/$STORAGE_API_TOKEN/" -i "parameters.yml.dist"

exit $?
