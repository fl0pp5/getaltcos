#  Сборка образа ALTCOS в формате QCOW2 с нуля

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

Сборка образа
```
sudo ./getaltcos/ostree/bin/make_qcow2.sh out/1.qcow2 out/$VERSION_DATE/0/0/var $OSTREE_BRANCH $MAIN_REPO
```

Запуск с передачей конфигурационного файла ignition. Пример файла можно взять [тут](http://git.altlinux.org/gears/s/startup-installer-altcos.git?p=startup-installer-altcos.git;a=blob;f=altcos/config_example.ign;h=c29510932fb36a0b88e8c2b1079a1687318b3798;hb=96148075e0f0f74b0cfa31439adfbac337fc34e5)
```
sudo qemu-system-x86_64 -m 1024 -machine accel=kvm -cpu host -hda out/1.qcow2 -net user,hostfwd=tcp::10222-:22 -net nic -fw_cfg name=opt/com.coreos/config,file=/home/keremet/src/startup-installer-acos/acos/config_example.ign
```
