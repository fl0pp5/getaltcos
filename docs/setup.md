# Установка и настройка WEB-сервера

Установка пакетов:
```
# apt-get update
# apt-get install apache2 apache2-mod_php7 php7_curl php7-mbsting php7
```

Добавление виртуального WWW-сервера в файле `/etc/httpd2/conf/sites-available/vhosts.conf`:
```
<VirtualHost *:80>
       ServerAdmin kaf@altlinus.org     
       DocumentRoot "/var/www/vhosts/getacos"
       ServerName getacos.altlinux.org
       ServerAlias acos.altlinux.org 
       ServerAlias builds.acos.altlinux.org
       ErrorLog "/var/log/httpd2/getacos/error.log"
       CustomLog "/var/log/httpd2/getacos/access.log" common
</VirtualHost>
```

Создание каталогов логов сайта:
```
# mkdir -p /var/www/vhosts/getacos
# chown root:webmaster  /var/www/vhosts/getacos
```

Включение пользователя в группу webmaster:
```
# usermod  -a -G webmaster <пользователь>
```

Копирование репозитория (из под обвчного пользователя-разработчика):
```
$ cd /var/www/vhosts/
$ git clone https://gitea.basealt.ru/kaf/getacos
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
