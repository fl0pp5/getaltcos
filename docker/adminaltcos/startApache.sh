#!/bin/sh
set -x
rm -f /var/run/httpd2/httpd.pid;
if [ -z "$MODULES" ]
then
  MODULES="rewrite ssl deflate filter"
fi

mkdir -m 0775 -p /var/www/html/altcos/ALTCOS/streams/altcos/sisyphus
chgrp -R root:webmaster /var/www/html/altcos/ALTCOS/

for module in $MODULES
do
  a2enmod $module
done

/usr/sbin/httpd2 -D NO_DETACH -k start 2>&1

