# Установка и настрока WEB-серверов для административного и клиентского WEB/REST интерфейсов

Для работы с ostree-репозиториями и образами ACOS поддерживаются два интерфейса:
- административный интерфейс на порту 81;
- клиентский интерфейс на порту 80;

Административный интерфейс предназначен для создания, обновления версий веток и подветок потоков:
- доступен только в рамках подсети Базальт;
- работает с корнем данного репозитория (начальная страница /index.php), 
- пользователь apache2 под которым работает сайт входит в группу wheel, позволяющей выполнять shell скрипты с правами root;
- все каталоги подкаталога данных ACOS доступны на чтение-запись.

Клиентский интерфейс преднаначен для предоставлении информации клиенту, скачивания образов и обровлений из архивного ostree-репозитория:
- доступен из Интернета;
- корневым директорием является подкаталог данных /ACOS, вышележащие каталоги с скрипты недоступны;
- пользователь apache2 под которым работает сайт имеет стандартные права;
- корневой каталог /ACOS монтируется ТОЛЬКО НА ЧТЕНИЕ (RO);

Оба интерфейса запускаются в виде docker-сервисов через docker-compose.

## Сборка docker-образа getacos клиентского интерфейса
Сборка производится в каталоге [/docker/getacos](https://github.com/alt-cloud/getacos/tree/feature-acosfile/docker/getacos).


### [Dockerfile](https://github.com/alt-cloud/getacos/blob/feature-acosfile/docker/getacos/Dockerfile).

Сборка идет от docker-образа `alt:sisyphus`. В образ устанавливается основные пакеты для работы:
```
apache2 apache2-mod_ssl 
apache2-mod_php7 php7-curl php7-mbstring php7  
ostree 
rsync 
vim-console 
less
```

### Стартовый скрипт [startApache.sh](https://github.com/alt-cloud/getacos/blob/feature-acosfile/docker/getacos/startApache.sh)

Для того, чтобы данные в каталоге данных `ACOS` в административном и клиентском  интерфейсе 
были доступны под одним и тем же URL `/ACOS/...` в подкаталоге `/ACOS/` для клиенского сервера создается сиволическая ссылка 
ACOS на корневой директорий  `ACOS -> .`.

Затем запускается apache2-сервер.

### Скрипт сборки образа [build.sh](https://github.com/alt-cloud/getacos/blob/feature-acosfile/docker/getacos/build.sh)

Скрипт предназначен для сборки docker-образа `getacos`.


## Сборка docker-образа admingetacos административного интерфейса
Сборка производится в каталоге [/docker/admingetacos](https://github.com/alt-cloud/getacos/tree/feature-acosfile/docker/admingetacos).


### [Dockerfile](https://github.com/alt-cloud/getacos/blob/feature-acosfile/docker/admingetacos/Dockerfile).

Для уменьшение суммарного объема образов на диске и в оперативной памяти
сборка образа `admingetacos` идет от docker-образа `getacos`, описанного выше. 
В этом случае в образе `admingetacos` наследуются основные слои образа `getacos`.


