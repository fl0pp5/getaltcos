#!/bin/sh
export DOCUMENT_ROOT=/var/www/vhosts/getacos
if [ `id -u` -ne 0 ]
then
	echo "Запуск скрипта $0 возможет только от пользователя root (uid=0)"
	exit 1
fi

export REFPREFIX=alt/x86_64/acos
export STREAM=sisyphus
export REF=$REFPREFIX/$STREAM
export VERSION
export VERSIONSTREAM=$STREAM
export VERSIONDATE=`date +%Y%m%d`
export VERSIONMAJOR=0
export VERSIONMINOR=0
export VERSION="$VERSIONSTREAM.$VERSIONDATE.$VERSIONMAJOR.$VERSIONMINOR"

export LANh=C
REPOHOME=/var/www/vhosts/getacos/ACOS/streams/$REF
RAWHOME=/var/www/vhosts/getacos/ACOS/images/$REF

UPDATEIP="10.4.4.199"
ACOSURL="http://acos.altlinux.org"
REMOTEREPOURL="$ACOSURL/ACOS/streams/$REF/archive/repo/"
CINCINATTIGRAPHURL="${ACOSURL}/"


refDir=$DOCUMENT_ROOT/ACOS/streams/$REF/roots
rootDir="$refDir/root"
rm -rf $refDir

mkdir -p $rootDir

tar -C $rootDir -xf ~/out/acos-latest-x86_64.tar

cd $rootDir
#Раскидать каталоги по /var, /usr
#/home
rm -rf ./var/home
mv ./home ./var
ln -sf /var/home home

#/mnt
mv ./mnt ./var
ln -sf /var/mnt mnt

#/opt
mv ./opt ./var
ln -sf /var/opt opt

#/root
mv ./root ./var/roothome
ln -sf /var/roothome root

#/srv
mv ./srv ./var
ln -sf /var/srv srv

sed -i 's|^HOME=/home$|HOME=/var/home|g' ./etc/default/useradd
chmod 777 ./etc/sudoers
sed -i 's/^# WHEEL_USERS ALL=(ALL) NOPASSWD: ALL$/WHEEL_USERS ALL=(ALL) NOPASSWD: ALL/g' ./etc/sudoers


chmod 400 ./etc/sudoers

cd

#ОТЛАДОЧНЫЕ ПАКЕТЫ КОТОРЫЕ СТОИТ УДАЛИТЬ ИЗ КОНЕЧНОГО РЕЛИЗА
apt-get install -y -o RPM::RootDir=$rootDir vim-console apt-repo apt # apt apt-repo # glibc-locales #tcpdump curl telnet

apt-get install -y -o RPM::RootDir=$rootDir ~/rpms/*.rpm sudo

#groupadd -R $rootDir sudo
usermod -R $rootDir -a -G root,wheel zincati

#chmod 777 $rootDir/etc/sudoers.d
#echo "# https://github.com/openshift/os/issues/96
#%sudo       ALL=(ALL)       NOPASSWD: ALL" > $rootDir/etc/sudoers.d/coreos-sudo-group
#chmod 700 $rootDir/etc/sudoers.d

chgrp wheel /usr/bin/sudo /bin/su
chmod 710 /usr/bin/sudo /bin/su
chmod ug+s /usr/bin/sudo /bin/su

mkdir -p $rootDir/etc/ostree/remotes.d/
echo "
[remote \"acos\"]
url=$REMOTEREPOURL
gpg-verify=false
" > $rootDir/etc/ostree/remotes.d/acos.conf
echo "
# ALTLinux CoreOS Cincinnati backend
[cincinnati]
base_url=\"$CINCINATTIGRAPHURL\"
" > $rootDir/etc/zincati/config.d/50-fedora-coreos-cincinnati.toml

echo "$UPDATEIP acos.altlinux.org" >> $rootDir/etc/hosts

chroot $rootDir systemctl enable zincati.service

#exit 0
cd $rootDir

rm -f  `find . ! -type f -a ! -type d -a ! -type l`
rm -rf ./usr/etc
mkdir ./usr/etc
cd etc
find . -depth | cpio -plmd ../usr/etc
cd ..
rm -rf ./etc


mkdir sysroot
ln -s sysroot/ostree ostree


# СОЗДАНИЕ РЕПОЗИТОРИЯ

if [ -d $REPOHOME ]
then
  chattr -iR $REPOHOME
  rm -rf $REPOHOME/archive
  rm -rf $REPOHOME/bare
fi
mkdir -p $REPOHOME/archive
mkdir -p $REPOHOME/bare
mkdir -p $REPOHOME/roots
#exit 0

cd $rootDir
KERNEL=`find ./boot/ -type f -name "vmlinuz-*"`
SHA=`sha256sum "$KERNEL" | awk '{print $1;}'`
mv "$KERNEL" "$KERNEL-$SHA"
rm -f ./boot/vmlinuz
rm -f ./boot/initrd*
chroot ./ \
	dracut --reproducible \
	--gzip -v \
	--add ostree \
	-f /boot/initramfs-$SHA  \
	--no-hostonly \
	--omit=nfs \
	--omit=lvm \
	--omit=iscsi \
	--kver `ls ./lib/modules`


ostree init --repo=$REPOHOME/archive/repo --mode=archive
ostree init --repo=$REPOHOME/bare/repo --mode=bare

ostree commit \
	--repo=$REPOHOME/bare/repo \
	--tree=dir=. \
	-b $REF\
	--no-xattrs \
	--no-bindings \
	--parent=none \
	--mode-ro-executables \
	--add-metadata-string=version=$VERSION

#        --owner-uid 0 --owner-gid 0 \

ostree  summary --repo=$REPOHOME/bare/repo --update

ostree pull-local --repo $REPOHOME/archive/repo $REPOHOME/bare/repo
ostree  summary --repo=$REPOHOME/archive/repo --update

# СОЗДАТЬ ДИСК

export DEVICE=/dev/sdb
export PART=${DEVICE}1
export MOUNTPOINT=/tmp/acos

set -- `mount | grep 'on / '`
rootDev=$1
len=${#DEVICE}
if [ ${rootDev:0:$len}  = $DEVICE ]
then
	echo "Устройство $DEVICE уже занято под корневую файловую систему"
	exit 1
fi

if ! fdisk -l $DEVICE >/dev/null 2>&1
then
	echo "Устройство $DEVICE недоступно"
	exit 1
fi

if fdisk -l $DEVICE | grep $PART >/dev/null 2>&1
then
	echo -ne "Linux партиция $PART уже создана. Удалить и создать заново? (y/N): "
	read answer
	if [ "$answer" != 'y' ]
	then
		exit 2
	else
		umount -f $MOUNTPOINT
		dd if=/dev/zero of=$DEVICE bs=1M
	fi

fi

mkdir -p $MOUNTPOINT
parted $DEVICE mktable msdos
parted -a optimal $DEVICE mkpart primary ext4 2MIB 100%
parted $DEVICE set 1 boot on
mkfs.ext4 -L ROOT $PART
mount $PART $MOUNTPOINT
ostree admin init-fs --modern $MOUNTPOINT

grub-install --root-directory=$MOUNTPOINT $DEVICE
ln -s ../loader/grub.cfg $MOUNTPOINT/boot/grub/grub.cfg

ostree pull-local --repo $MOUNTPOINT/ostree/repo $REPOHOME/archive/repo $REF
ostree config --repo $MOUNTPOINT/ostree/repo set sysroot.bootloader grub2
ostree refs --repo $MOUNTPOINT/ostree/repo --create acos:$REF $REF
ostree admin os-init alt-acos --sysroot $MOUNTPOINT
export OSTREE_BOOT_PARTITION="/boot" 
ostree admin deploy acos:$REF \
	--sysroot $MOUNTPOINT \
	--os alt-acos \
	--karg-append=quiet --karg-append=root=UUID=`blkid --match-tag UUID -o value $PART`

echo "Задайте пароль root:"
chroot $MOUNTPOINT/ostree/boot.1/alt-acos/*/0/ passwd

echo "Задайте пароль zincati:"
chroot $MOUNTPOINT/ostree/boot.1/alt-acos/*/0/ passwd zincati

umount $MOUNTPOINT

mkdir -p $RAWHOME
dd if=/dev/sdb1 of=$RAWHOME/$VERSIONDATE.raw



