#  Сборка архива корневой файловой системы, которая будет взята за основу при создании коммита в репозиторий ostree

Установить пакеты
```
sudo apt-get install mkimage mkimage-preinstall hasher git-core
```

Добавить своего пользователя (в данном случае - keremet) в группы, необходимые для запуска hasher
```
sudo hasher-useradd keremet
```

Перелогиниться.

В /etc/hasher-priv/system добавить строчку: allowed_mountpoints=/proc

Создать конфигурационные файлы для сборки из репозитория Sisyphus с кэшированием
```
mkdir -p ~/apt/lists/partial
mkdir -p ~/apt/cache/sisyphus/archives/partial

cat <<EOF > ~/apt/apt.conf.sisyphus.x86_64 
Dir::Etc::SourceList "$HOME/apt/sources.list.sisyphus.x86_64";
Dir::Etc::SourceParts /var/empty;
Dir::Etc::main "/dev/null";
Dir::Etc::parts "/var/empty";
APT::Architecture "64";
Dir::State::lists "$HOME/apt/lists/";
Dir::Cache "$HOME/apt/cache/sisyphus/";
EOF

cat <<EOF > ~/apt/sources.list.sisyphus.x86_64
rpm [alt] http://mirror.yandex.ru/altlinux Sisyphus/x86_64 classic
rpm [alt] http://mirror.yandex.ru/altlinux Sisyphus/noarch classic
EOF
```

Скачать mkimage-profiles
```
git clone git://git.altlinux.org/gears/m/mkimage-profiles.git
```

Сборка acos.tar. Если создан каталог ~/out, то результат сборки будет располагаться в нем, иначе - в каталоге $TMP/out.
```
cd mkimage-profiles
make DEBUG=1 APTCONF=~/apt/apt.conf.sisyphus.x86_64 BRANCH=sisyphus ARCH=x86_64 vm/acos.tar
```
