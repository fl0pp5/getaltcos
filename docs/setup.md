# Конфигурация, установка и настройка WEB-сервера

## Конфигурация WEB-сервера

WEB-сервер Apache2 с поддержкой модуля PHP имеет домен
`http://getacos.altlinux.org` с алиасами `http://acos.altlinux.org`, `http://builds.acos.altlinux.org`.

В дальнейшем при выводе системы в промышленную эксплуатацию необходимо будет добавить домены для тестового контура.

## Установка и настройка WEB-сервера

Установка пакетов:
```
# apt-get update
# apt-get install -y apache2 apache2-mod_php7 php7-curl php7-mbstring php7
```

Добавление виртуального WWW-сервера в файле `/etc/httpd2/conf/sites-available/vhosts.conf`:
```
<VirtualHost *:80>
       ServerAdmin user@domain
       DocumentRoot "/var/www/vhosts/getacos"
       ServerName getacos.altlinux.org
       ServerAlias acos.altlinux.org
       ServerAlias builds.acos.altlinux.org
       ErrorLog "/var/log/httpd2/getacos/error.log"
       CustomLog "/var/log/httpd2/getacos/access.log" common
       <Directory /var/www/vhosts/getacos>
         Options Indexes FollowSymLinks
       </Directory>
</VirtualHost>
```


Включение пользователя в группу webmaster:
```
# usermod  -a -G webmaster <пользователь>
# usermod  -a -G wheel apache2
```

Копирование репозитория (из под обычного пользователя-разработчика):
```
$ cd /var/www/vhosts/
$ git clone git@github.com:alt-cloud/getacos.git
```

Включение поддержки виртуальных сайтов:
```
# chown root:webmaster  /var/www/vhosts/getacos
# a2ensite vhosts
# mkdir /var/log/httpd2/getacos/
# chmod 777  /var/log/httpd2/getacos
# mkdir -p  /var/www/vhosts/getacos/ACOS/streams/acos/
# chgrp root:webmaster /var/www/vhosts/getacos/ACOS/streams/acos/

# apt-get install mkimage mkimage-preinstall hasher git-core

```



Запуск сервера:
```
# systemctl enable httpd2
# systemctl start httpd2
```

Настройка доступа к серверу в файле `/etc/hosts`:
```
...
<внешний_IP-адрес> getacos.altlinux.org acos.altlinux.org builds.acos.altlinux.org
```

## Установка  mkimages-profiles

```
$ cd /var/www/vhosts
$ git clone  git://git.altlinux.org/gears/m/mkimage-profiles.git
```
