FROM getacos

ENV LANG ru_RU.UTF-8

MAINTAINER kaf@altlinux.org

RUN \
  apt-get install -y sudo ostree; \
  usermod  -a -G wheel apache2; \
  chmod 777 /etc/sudoers; \
  sed -i -e "s/# WHEEL_USERS ALL=(ALL) NOPASSWD: ALL/WHEEL_USERS ALL=(ALL) NOPASSWD: ALL/" /etc/sudoers; \
  chmod 400 /etc/sudoers; \
  sed -e 's/Options.*/Options Indexes FollowSymLinks/' -i /etc/httpd2/conf/include/Directory_root_default.conf

COPY /startApache.sh /bin/startApache.sh

EXPOSE 80

CMD [ "/bin/startApache.sh" ]


