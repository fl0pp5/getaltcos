FROM quay.io/kafbasealt/uialtcos

ENV LANG ru_RU.UTF-8

MAINTAINER kaf@altlinux.org

RUN \
  apt-get install -y sudo su isomd5sum rpm-build; \
  usermod  -a -G wheel apache2; \
  chmod 777 /etc/sudoers; \
  sed -i -e "s/# WHEEL_USERS ALL=(ALL) NOPASSWD: ALL/WHEEL_USERS ALL=(ALL) NOPASSWD: ALL/" /etc/sudoers; \
  chmod 400 /etc/sudoers; \
  sed -e 's/Options.*/Options +Indexes +FollowSymLinks/' -i /etc/httpd2/conf/include/Directory_root_default.conf; \
  echo -ne "<VirtualHost *:80>\n  ServerAdmin user@domain\n  DocumentRoot "/var/www/vhosts/altcos"\n  ServerName adminaltcos.altlinux.org\n  ServerAlias builds.altcos.altlinux.org\n  ErrorLog \"/var/log/httpd2/error.log\"\n  CustomLog \"/var/log/httpd2/access.log\" common\n  <Directory /var/www/vhosts/altcos>\n Options Indexes FollowSymLinks\n  </Directory>\n</VirtualHost>" >> /etc/httpd2/conf/sites-available/vhosts.conf;\
  a2ensite vhosts; \
  adduser -d /var/www/vhosts/altcos altcos


COPY /startApache.sh /bin/startApache.sh

EXPOSE 80

CMD [ "/bin/startApache.sh" ]


