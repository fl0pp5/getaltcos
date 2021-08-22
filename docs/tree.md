# Конфигурация WEB-сервера

WEB-сервер Apache2 с поддержкой модуля PHP имеет домен
`http://getacos.altlinux.org` с алиасами `http://acos.altlinux.org`, `http://builds.acos.altlinux.org`.

В дальнейшем при выводе системы в промышленную эксплуатацию необходимо будет добавить домены для тестового контура.

## Установка и настройка WEB-сервера

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


## Дерево файловой системы WEB-сервера

Дерево файловой системы WEB-сервера выглядит сдежующим образом:
![Дерево файловой системы WEB-сервера](Images/tree.png)

Корневые каталоги:
- `v1/graph`к - PHP-скрипт(ы) сервера графа, обеспечиваюшие доступ к дереву веток репозитория по протоколу `cincinatti`;
- `ostree` - CLI и PHP скрипты создания репозитория и его веток;
- `ACOS` - каталог ostree-репозиториев и промежуточных данных для них.

Каталоги `v1` и `ostree` разворачиваются и поддерживаются из git-репозитория [https://gitea.basealt.ru/kaf/getacos](https://gitea.basealt.ru/kaf/getacos).

Каталог `ACOS` формируется динамически скриптами каталога `ostree` доступны скриптам формирования графа каталога `v1`.

### Структура корневого каталога скриптов `/ostree` и производного каталога репозиториев `/ACOS`

Корневой каталог скриптов `/ostree` содержит подкаталоги:
- `bin/` - каталог shell-скриптов для вызова в режиме CLI или WEB-интерфейса из нижеописанных каталогов `update/`, `install/`.
- `update/` - содержит единственный скрипт `index.php`, обеспечивающий по REST-интерфейсу `http://getacos.altlinux.org/ostree/update/` обновление текущей версии репозитория (при их наличии) до следующей версии.
- `install` - содержит единственный скрипт `index.php`, обеспечивающий по REST-интерфейсу `http://getacos.altlinux.org/ostree/install/` форморование новой ветки репозитория с указанными для установке пакетами


#### Структура каталога shell-скриптов `/ostree/bin`

Перед запуском скриптов должна быть определена переменная `DOCUMENT_ROOT` хранящую тропу до корневого репозитория:
```
# export DOCUMENT_ROOT=/var/www/vhosts/getacos
```

- `createACOStar.sh` - создание tar-файла `~/out/acos-<date>-x86_64.tar` начального дистрибутива с символической ссылкой `~out/atacos-latest-x86_64.tar`.
- `createRepo.sh` - создание репозитория на основе tar-файла 
- `ostree_log.sh` - отображение логов  по ссылке (`refs`).
Формат:
```
ostree_log.sh ref
```

Пример:
```
$ ostree_log.sh acos/x86_64/sisyphus
commit fafb6a2406f09bfee76d7d0565c32dd13743f79019d8ff32b2a0c40137e332b6
ContentChecksum:  90456ba35e9af8bccf61bb3516e21dd695feae4a948d2bdc0714a81bce262a26
Date:  2021-08-20 15:49:26 +0000
Version: sisyphus.20210820.0.0
(no subject)
```

- `ostree_checkout.sh` - разворачивание дерева дистрибутива в каталоге `/var/www/vhosts/getacos/ACOS/streams/acos/x86_64/sisyphus/roots/<commitId>/` и монтирование overlay-каталогов.
Формат вызова:
```
ostree_checkout.sh ref commitId clear
```

Пример:
```
$ ostree_checkout.sh acos/x86_64/sisyphus fafb6a2406f09bfee76d7d0565c32dd13743f79019d8ff32b2a0c40137e332b6 all
```
Действия:
- в случае значения 3-го параметра `all` удаление каталога 
`/var/www/vhosts/getacos/ACOS/streams/acos/x86_64/sisyphus/roots`
и создание нового каталога `roots`;
- разворачивание из каталога репозитория `/var/www/vhosts/getacos/ACOS/streams/acos/x86_64/sisyphus/bare/repo` в каталог `/var/www/vhosts/getacos/ACOS/streams/acos/x86_64/sisyphus/roots/fafb6a2406f09bfee76d7d0565c32dd13743f79019d8ff32b2a0c40137e332b6` ветки `acos/x86_64/sisyphus` репозитория с комитом `fafb6a2406f09bfee76d7d0565c32dd13743f79019d8ff32b2a0c40137e332b6`.
- создание символической ссылки `root` на каталог `fafb6a2406f09bfee76d7d0565c32dd13743f79019d8ff32b2a0c40137e332b6`;
- создание подкаталогов `merged`, `upper`, `work`.

- overlay-монтировние каталогов:
```
mount -t overlay overlay -o lowerdir=fafb6a2406f09bfee76d7d0565c32dd13743f79019d8ff32b2a0c40137e332b6,upperdir=./upper,workdir=./work ./merged
```

Нижний каталог `fafb6a2406f09bfee76d7d0565c32dd13743f79019d8ff32b2a0c40137e332b6` монтируется в режиме `только на чтение`.
На него формируется символическая ссылка `root`.

На момент монтирования содержимое каталога `upper` совпадает с содержимым каталога развернутого комита `root`.
Работа по корректировке развернутого дерева репозитория в дальнейшем производится в каталог `merged`. Все изменения, добавления и удаление файлов в каталог `merged` отображаются в каталоге `upper`. Содержимое каталога `root` остается неизменным.

- `apt-get_update.sh` - обновление пакетной базы в каталоге `merged`.

Формат вызова:
```
apt-get_update.sh ref
```

- `apt-get_dist-upgrade.sh` - установка обновленных пакетов в каталоге `merged`.

Формат вызова:
```
apt-get_dist-upgrade.sh ref
```

- `syncUpdates.sh` - передача обновления из каталога `upper` в каталог комита `root`.

Формат вызова:
```
syncUpdates.sh ref
```

Файлы, помеченные как специальные файлы типа `character` удаляются из каталогов `upper` и `root`.
Остальное содержимое каталога `upper` копируются в каталог комита `root`. 

(Рассмотреть вариант создание комита на основе каталога `merged`).

- `ostree_commit.sh` - соссздание нового комита в `bare`-репозитории на основе нового содержимого каталога `root`.

Формат вызова:
```
ostree_commit.sh ref newCommitId newVersion
```

(Рассмотреть вариант создание комита на основе каталога `merged`).


- `ostree_pull-local.sh` -


### Структура каталога `/v1/graph` графа протокола `cincinatti`