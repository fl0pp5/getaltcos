#  Сборка установочного ISO-образа ALTCOS

## Подготовка ОС
Установить пакеты
```
sudo apt-get install mkimage mkimage-preinstall hasher git-core ostree
```

Добавить своего пользователя (в данном случае - keremet) в группы, необходимые для запуска hasher
```
sudo hasher-useradd keremet
```

В /etc/hasher-priv/system добавить строчку: allowed_mountpoints=/proc

Перелогиниться.

## Подготовка каталога для сборки

Создать каталог и перейти в него
```
mkdir altcos_build
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


Создать файл спецификации пакета altcos-archives
```
cat <<EOF > altcos-archives.spec
Name: altcos-archives
Version: 0.1
Release: alt1

Summary: Archives to install ALTCOS
License: GPL-3.0-or-later
Group: System/Base

%description
Archives to install ALT Container OS

%install
mkdir -p %buildroot%_datadir/altcos/
install -m444 ../SOURCES/altcos_root.tar.xz %buildroot%_datadir/altcos/
install -m444 ../SOURCES/var.tar.xz %buildroot%_datadir/altcos/

%files
%_datadir/altcos/altcos_root.tar.xz
%_datadir/altcos/var.tar.xz
EOF
```

Скачать mkimage-profiles и getaltcos
```
git clone http://git.altlinux.org/people/keremet/packages/mkimage-profiles.git -b altcos
git clone https://github.com/alt-cloud/getaltcos
```

## Сборка
Установить переменные окружения
```
MAIN_REPO=repo
export BRANCH=sisyphus
export ARCH=x86_64
OSTREE_BRANCH=altcos/$ARCH/$BRANCH
```

Сборка altcos.tar. Результат сборки будет располагаться в каталоге out.
```
make -C mkimage-profiles DEBUG=1 APTCONF=$PWD/apt/apt.conf.sisyphus.x86_64 IMAGEDIR=$PWD/out vm/altcos.tar
```

Создать коммит в репозитории ostree.
```
VERSION_DATE=$(basename `realpath out/altcos-latest-x86_64.tar`| awk -F- '{print $2;}')
sudo rm -rf out/$VERSION_DATE/0/0
sudo ./getaltcos/ostree/bin/rootfs_to_repo.sh $OSTREE_BRANCH out/altcos-latest-x86_64.tar $MAIN_REPO out
```

Создать пакет altcos-archives с архивами для установки. Указать путь к каталогу var, созданному предыдущей командой в каталоге out (скорректировать дату).
```
sudo tar -cf - -C out/$VERSION_DATE/0/0 var | xz -9 -c - > rpmbuild/SOURCES/var.tar.xz

sudo rm -rf altcos_root
mkdir altcos_root
ostree admin init-fs --modern altcos_root
sudo ostree pull-local --repo altcos_root/ostree/repo $MAIN_REPO $OSTREE_BRANCH
sudo tar -cf - -C altcos_root . | xz -9 -c -T0 - > rpmbuild/SOURCES/altcos_root.tar.xz

rpmbuild --define "_topdir $PWD/rpmbuild" --define "_rpmdir $PWD/x86_64/RPMS.dir/" --define "_rpmfilename altcos-archives-0.1-alt1.x86_64.rpm" -bb altcos-archives.spec
```

Сборка установочного образа
```
make -C mkimage-profiles DEBUG=1 APTCONF=$PWD/apt/apt.conf.sisyphus.x86_64 IMAGEDIR=$PWD/out installer-altcos.iso
```
