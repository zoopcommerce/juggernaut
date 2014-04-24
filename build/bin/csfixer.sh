#!/bin/bash

if [ ! -f /usr/local/bin/php-cs-fixer.phar ]
then
    sudo wget http://get.sensiolabs.org/php-cs-fixer.phar -O /usr/local/bin/php-cs-fixer.phar
fi

php /usr/local/bin/php-cs-fixer.phar fix ./lib --level=psr2
php /usr/local/bin/php-cs-fixer.phar fix ./tests --level=psr2