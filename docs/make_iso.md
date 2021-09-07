#  Сборка установочного ISO-образа ACOS

## Подготовка ОС
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

## Подготовка каталога для сборки

Создать каталог и перейти в него
```
mkdir acos_build
cd $_
```

Создать конфигурационные файлы для сборки из репозитория Sisyphus с кэшированием
```
mkdir -p apt/lists/partial
mkdir -p apt/cache/sisyphus/archives/partial
mkdir -p rpmbuild/SOURCES
mkdir -p x86_64/RPMS.dir
mkdir out

cat <<EOF > apt/apt.conf.sisyphus.x86_64 
Dir::Etc::SourceList "$PWD/apt/sources.list.sisyphus.x86_64";
Dir::Etc::SourceParts /var/empty;
Dir::Etc::main "/dev/null";
Dir::Etc::parts "/var/empty";
APT::Architecture "64";
Dir::State::lists "$PWD/apt/lists/";
Dir::Cache "$PWD/apt/cache/sisyphus/";
EOF

cat <<EOF > apt/sources.list.sisyphus.x86_64
rpm [alt] http://mirror.yandex.ru/altlinux Sisyphus/x86_64 classic
rpm [alt] http://mirror.yandex.ru/altlinux Sisyphus/noarch classic
rpm-dir file:$PWD x86_64 dir
EOF
```


Создать файл спецификации пакета acos-archives
```
cat <<EOF > acos-archives.spec
Name: acos-archives
Version: 0.1
Release: alt1

Summary: Archives to install ACOS
License: GPL-3.0-or-later
Group: System/Base

%description
Archives to install ALT Container OS

%install
mkdir -p %buildroot%_datadir/acos/
install -m444 ../SOURCES/acos_root.tar.xz %buildroot%_datadir/acos/
install -m444 ../SOURCES/var.tar.xz %buildroot%_datadir/acos/

%files
%_datadir/acos/acos_root.tar.xz
%_datadir/acos/var.tar.xz
EOF
```

Скачать mkimage-profiles и getacos
```
git clone http://git.altlinux.org/people/keremet/packages/mkimage-profiles.git -b acos
git clone https://github.com/alt-cloud/getacos
```

## Сборка
Установить переменные окружения
```
MAIN_REPO=repo
export BRANCH=sisyphus
export ARCH=x86_64
OSTREE_BRANCH=acos/$ARCH/$BRANCH
```

Сборка acos.tar. Результат сборки будет располагаться в каталоге out.
```
make -C mkimage-profiles DEBUG=1 APTCONF=$PWD/apt/apt.conf.sisyphus.x86_64 IMAGEDIR=$PWD/out vm/acos.tar
```

Создать коммит в репозитории ostree. В команде указать архив, созданный предыдущей командой
```
sudo ./getacos/ostree/bin/rootfs_to_repo.sh $OSTREE_BRANCH out/acos-20210906-x86_64.tar $MAIN_REPO out
```

Создать пакет acos-archives с архивами для установки. Указать путь к каталогу var, созданному предыдущей командой в каталоге out (скорректировать дату).
```
sudo tar -cf - -C out/20210906/0/0 var | xz -9 -c - > rpmbuild/SOURCES/var.tar.xz

sudo rm -rf acos_root
mkdir acos_root
ostree admin init-fs --modern acos_root
sudo ostree pull-local --repo acos_root/ostree/repo $MAIN_REPO $OSTREE_BRANCH
sudo tar -cf - -C acos_root . | xz -9 -c -T0 - > rpmbuild/SOURCES/acos_root.tar.xz

rpmbuild --define "_topdir $PWD/rpmbuild" --define "_rpmdir $PWD/x86_64/RPMS.dir/" --define "_rpmfilename acos-archives-0.1-alt1.x86_64.rpm" -bb acos-archives.spec
```

Сборка установочного образа
```
make -C mkimage-profiles DEBUG=1 APTCONF=$PWD/apt/apt.conf.sisyphus.x86_64 IMAGEDIR=$PWD/out installer-acos.iso
```
