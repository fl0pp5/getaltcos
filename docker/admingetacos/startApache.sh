#!/bin/sh
set -x
rm -f /var/run/httpd2/httpd.pid;
if [ -z "$MODULES" ]
then
  MODULES="rewrite ssl deflate filter"
fi

mkdir -m 0775 -p /var/www/html/getacos/ACOS/streams/acos/sisyphus
chgrp -R root:webmaster /var/www/html/getacos/ACOS/

for module in $MODULES
do
  a2enmod $module
done

/usr/sbin/httpd2 -D NO_DETACH -k start 2>&1

