#!/bin/bash

export COMPOSER_PROCESS_TIMEOUT=50000
if [ ! -f /opt/composer/composer.phar ]
then
    mkdir -p /opt/composer/
    curl -s https://getcomposer.org/installer | php -- --install-dir=/opt/composer/
    /opt/composer/composer.phar install --no-dev --optimize-autoloader
else
    /opt/composer/composer.phar self-update
    if [ -f composer.lock ] && [ "$SERVER_TYPE" == "production" ]
    then
        /opt/composer/composer.phar install --no-dev --optimize-autoloader
    else
        /opt/composer/composer.phar update --optimize-autoloader -vvv
    fi
fi