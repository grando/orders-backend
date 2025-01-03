#!/bin/bash

DOC_ROOT=/var/www/html

if [ "$RUNNING_MODE" != "BUILD" ]; then

cp $DOC_ROOT/docker/conf/vhost/vhosts-$RUNNING_MODE.conf  /etc/apache2/sites-available/
cp $DOC_ROOT/docker/conf/http/.httpd  /etc/apache2/sites-available/
cp $DOC_ROOT/docker/conf/php/20-php-overrides.ini  /etc/php/7.3/apache2/conf.d/20-php-overrides.ini

/usr/sbin/a2ensite vhosts-$RUNNING_MODE.conf

  # Run the apache service
  source /etc/apache2/envvars
  exec apache2 -D FOREGROUND

else
  tail -F /var/log/apache2/*
fi
