FROM alt

ENV LANG ru_RU.UTF-8

MAINTAINER kaf@altlinux.org

RUN \
  apt-get update && \
  apt-get install -y apache2 apache2-mod_ssl apache2-mod_php7 php7-curl php7-mbstring php7

COPY /startApache.sh /bin/startApache.sh

EXPOSE 80

CMD [ "/bin/startApache.sh" ]
