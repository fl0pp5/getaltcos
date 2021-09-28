# Установка и настройка WEB-серверов для административного и клиентского WEB/REST интерфейсов

Для работы с ostree-репозиториями и образами ALTCOS поддерживаются два интерфейса:
- административный интерфейс на порту 81;
- клиентский интерфейс на порту 80;

Административный интерфейс предназначен для создания, обновления версий веток и подветок потоков:
- доступен только в рамках подсети Базальт;
- работает с корнем данного репозитория (начальная страница /index.php),
- пользователь apache2, под которым работает сайт, входит в группу wheel, позволяющую выполнять shell скрипты с правами root;
- все каталоги подкаталога данных ALTCOS доступны на чтение-запись.

Клиентский интерфейс предназначен для предоставлении информации клиенту, скачивания образов и обновлений из архивного ostree-репозитория:
- доступен из Интернета;
- корневым директорием является подкаталог данных ALTCOS, вышележащие каталоги и скрипты недоступны;
- пользователь apache2, под которым работает сайт, имеет стандартные права;
- корневой каталог ALTCOS монтируется ТОЛЬКО НА ЧТЕНИЕ (RO);

Оба интерфейса запускаются в виде docker-сервисов через docker-compose.

## Сборка docker-образа getaltcos клиентского интерфейса
Сборка производится в каталоге [docker/getaltcos](https://github.com/alt-cloud/getaltcos/tree/main/docker/getaltcos).


### [Dockerfile](https://github.com/alt-cloud/getaltcos/blob/main/docker/getaltcos/Dockerfile).

Сборка идет от docker-образа `alt:sisyphus`. В образ устанавливаются основные пакеты для работы:
```
apache2 apache2-mod_ssl
apache2-mod_php7 php7-curl php7-mbstring php7
ostree
rsync
vim-console
less
```

### Стартовый скрипт [startApache.sh](https://github.com/alt-cloud/getaltcos/blob/main/docker/getaltcos/startApache.sh)

Скрипт запускает apache2-сервер.

### Скрипт сборки образа [build.sh](https://github.com/alt-cloud/getaltcos/blob/main/docker/getaltcos/build.sh)

Скрипт предназначен для сборки docker-образа `getaltcos`.


## Сборка docker-образа admingetaltcos административного интерфейса
Сборка производится в каталоге [docker/admingetaltcos](https://github.com/alt-cloud/getaltcos/tree/main/docker/admingetaltcos).


### [Dockerfile](https://github.com/alt-cloud/getaltcos/blob/main/docker/admingetaltcos/Dockerfile).

Для уменьшения суммарного объема образов на диске и в оперативной памяти сборка образа `admingetaltcos` идет от docker-образа `getaltcos`, описанного выше.
В этом случае в образе `admingetaltcos` наследуются основные слои образа `getaltcos`.

В образе:
- дополнительно устанавливаются пакеты `sudo, su` для обеспечения доступа к правам root;
- пользователь `apache2` добавляется к группе `wheel`;
- правится файл `/etc/sudoers` для беспарольного доступа к правам `root`;
- для повышения уровня защиты поднимается виртуальный хост под доменами `admingetaltcos.altlinux.org`, `builds.altcos.altlinux.org`.

### Стартовый скрипт [startApache.sh](https://github.com/alt-cloud/getaltcos/blob/main/docker/admingetaltcos/startApache.sh)

Перед запуском сервера создается (если отсутствует) корневой каталог потока `altcos/x86_64/sisyphus`.

### Скрипт сборки образа [build.sh](https://github.com/alt-cloud/getaltcos/blob/main/docker/admingetaltcos/build.sh)

Скрипт предназначен для сборки docker-образа `admingetaltcos`.


## Запуск сервисов

Запуск сервисов производится в каталоге [docker/](https://github.com/alt-cloud/getaltcos/tree/main/docker).

### Файл установки переменных [.env](https://github.com/alt-cloud/getaltcos/blob/main/docker/.env)

Каталог, где установлен текущий git-репозиторий [getaltcos](https://github.com/alt-cloud/getaltcos/tree/main)
на локальном сервере указывается в файле [.env](https://github.com/alt-cloud/getaltcos/blob/main/docker/.env).

### Файл описания сервисов [docker-compose.yml](https://github.com/alt-cloud/getaltcos/blob/main/docker/docker-compose.yml)

Сервисы описываются в YML-файле [docker-compose.yml](https://github.com/alt-cloud/getaltcos/blob/main/docker/docker-compose.yml).

- сервис `getaltcos`:
  * пользовательский WEB-сервис привязывается к порту `80`.
  * корневой директорий сайта привязывается к поддиректорию данных [/ALTCOS/](https://github.com/alt-cloud/getaltcos/tree/main/ALTCOS).

- сервис `admingetaltcos`:
  * административный WEB-сервис привязывается к порту `81`.
  * повышаются привилегии процессов для поддержки оверлейного (`overlay`) монтирования каталогов;
  * корневой директорий сайта привязывается к корневому каталогу git-репозитория [/](https://github.com/alt-cloud/getaltcos/tree/main).

### Скрипт запуска сервисов [start-compose.sh](https://github.com/alt-cloud/getaltcos/blob/main/docker/start-compose.sh)

Скрипт (пере)запускает сервисы стека.

### Скрипт запуска сервисов [stop-compose.sh](https://github.com/alt-cloud/getaltcos/blob/main/docker/stop-compose.sh)

Скрипт останавливает сервисы стека.


## Порядок сборки образов и запуска сервисов

1. Убедитесь, что установлены пакеты `docker-engine`, `docker-compose` и запущен сервис `docker`.
Если нет, установите их:
```
# apt-get install docker-engine docker-compose
# systemctl enable --now docker
```

2. Перейдите в каталог `docker/getaltcos/` и запустите скрипт `build.sh` сборки образа `getaltcos`:
```
# cd getaltcos/docker/getaltcos/
# ./build.sh
...
Successfully built ....
Successfully tagged getaltcos:latest
```

3. Перейдите в каталог `docker/admingetaltcos/` и запустите скрипт `build.sh` сборки образа `admingetaltcos`:
```
# cd ../admingetaltcos/
# ./build.sh
...
Successfully built ....
Successfully tagged admingetaltcos:latest
```

4. Укажите в файле `docker/.env` каталог git-директория данного репозитория на локальном компьютере.

5. Перейдите в каталог `docker/` и запустите скрипт `start-compose.sh`:
```
# cd ..
# ./start-compose.sh
Creating network "docker_default" with the default driver
Creating docker_getaltcos_1      ... done
Creating docker_admingetaltcos_1 ... done
```


