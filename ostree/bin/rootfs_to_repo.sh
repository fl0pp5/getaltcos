#!/bin/sh
set -e

if [ $UID != 0 ]
then
	echo "Must run as superuser"
	exit 1
fi

if [ $# = 0 ] 
then
	echo "Help: $0 <branch> [<rootfs archive>] [<directory of main ostree repository>] [<directory for output archives>]"
	echo "For example: $0 acos/x86_64/sisyphus out/acos-20210824-x86_64.tar repo out"
	echo "You can change TMPDIR environment variable to set another directory where temporary files will be stored"
	echo "If directory of main ostree repository doesn't exists, new repository will be created"
	echo "Default values:"
	echo "- <rootfs archive> \$DOCUMENT_ROOT/ACOS/rootfs_archives/<branch>/acos-latest-x86_64.tar"
	echo "- <directory of main ostree repository> \$DOCUMENT_ROOT/ACOS/streams/<branch>/bare"
	echo "- <directory for output archives> \$DOCUMENT_ROOT/ACOS/streams/<branch>/install_archives"
	exit 1
fi

if [ `id -u` -ne 0 ]
then
        echo "ERROR: $0 needs to be run as root (uid=0) only"
        exit 1
fi

BRANCH=${1:-acos/x86_64/sisyphus}
ROOTFS_ARCHIVE="${2:-$DOCUMENT_ROOT/ACOS/rootfs_archives/$BRANCH/acos-latest-x86_64.tar}"
MAIN_REPO="${3:-$DOCUMENT_ROOT/ACOS/streams/$BRANCH/bare/repo}"
OUT_DIR="${4:-$DOCUMENT_ROOT/ACOS/install_archives/$BRANCH}"
RPMS_DIR=$DOCUMENT_ROOT/ostree/data/rpms

if [ ! -e $ROOTFS_ARCHIVE ]
then
	echo "ERROR: Rootfs archive must exist ($ROOTFS_ARCHIVE)"
	exit 1
fi

[ -L $ROOTFS_ARCHIVE ] && ROOTFS_ARCHIVE=`realpath $ROOTFS_ARCHIVE`

VERSION_DATE=`basename $ROOTFS_ARCHIVE | awk -F- '{print $2;}'`
echo "Date for version: $VERSION_DATE"

if ! [[ "$VERSION_DATE" =~ ^[0-9]{8}$ ]] 
then
	echo "ERROR: The name of the rootfs archive contains an incorrect date"
	exit 1
fi

VERSION_DIR=${OUT_DIR}/$VERSION_DATE

if [ -d $VERSION_DIR ]
then
	echo "ERROR: Version for date $VERSION_DATE already exists."
	exit 1
fi
mkdir -p $VERSION_DIR/0/0

VAR_ARCH=$VERSION_DIR/0/0/var.tar

rm -f $VAR_ARCH 

TMP_DIR=`mktemp --tmpdir -d rootfs_to_repo-XXXXXX`
MAIN_ROOT=$TMP_DIR/root

mkdir -p $MAIN_ROOT
tar xf $ROOTFS_ARCHIVE -C $MAIN_ROOT --exclude=./dev/tty --exclude=./dev/tty0 --exclude=./dev/console  --exclude=./dev/urandom --exclude=./dev/random --exclude=./dev/full --exclude=./dev/zero --exclude=/dev/null --exclude=./dev/pts/ptmx --exclude=./dev/null

#Вынести в m-i-p
rm -f $MAIN_ROOT/etc/resolv.conf
ln -sf /run/systemd/resolve/resolv.conf $MAIN_ROOT/etc/resolv.conf

chroot $MAIN_ROOT systemctl enable ignition-firstboot-complete.service ostree-remount.service sshd docker
sed -i 's/^LABEL=ROOT\t/LABEL=boot\t/g' $MAIN_ROOT/etc/fstab
sed -i 's/^AcceptEnv /#AcceptEnv /g' $MAIN_ROOT/etc/openssh/sshd_config
sed -i 's/^# WHEEL_USERS ALL=(ALL) ALL$/WHEEL_USERS ALL=(ALL) ALL/g' $MAIN_ROOT/etc/sudoers
echo "zincati ALL=NOPASSWD: ALL" > $MAIN_ROOT/etc/sudoers.d/zincati
sed -i 's|^HOME=/home$|HOME=/var/home|g' $MAIN_ROOT/etc/default/useradd
echo "blacklist floppy" > $MAIN_ROOT/etc/modprobe.d/blacklist-floppy.conf
mkdir $MAIN_ROOT/sysroot
ln -s sysroot/ostree $MAIN_ROOT/ostree

mv -f $MAIN_ROOT/home $MAIN_ROOT/opt $MAIN_ROOT/srv $MAIN_ROOT/mnt $MAIN_ROOT/var/
mv -f $MAIN_ROOT/root $MAIN_ROOT/var/roothome
mv -f $MAIN_ROOT/usr/local $MAIN_ROOT/var/usrlocal
ln -sf var/home $MAIN_ROOT/home
ln -sf var/opt $MAIN_ROOT/opt
ln -sf var/srv $MAIN_ROOT/srv
ln -sf var/roothome $MAIN_ROOT/root
ln -sf ../var/usrlocal $MAIN_ROOT/usr/local
ln -sf var/mnt $MAIN_ROOT/mnt

chroot $MAIN_ROOT chgrp wheel /usr/bin/sudo /bin/su
chroot $MAIN_ROOT chmod 710 /usr/bin/sudo /bin/su
chroot $MAIN_ROOT chmod ug+s /usr/bin/sudo /bin/su

# TUNE zincati
apt-get update -y -o RPM::RootDir=$MAIN_ROOT 
apt-get install -y -o RPM::RootDir=$MAIN_ROOT vim-console apt-repo apt
apt-get install -y -o RPM::RootDir=$MAIN_ROOT $RPMS_DIR/*.rpm sudo

usermod -R $MAIN_ROOT -a -G root,wheel zincati

mkdir -p $MAIN_ROOT/etc/ostree/remotes.d/
echo "
[remote \"acos\"]
url=$REMOTEREPOURL
gpg-verify=false
" > $MAIN_ROOT/etc/ostree/remotes.d/acos.conf
echo "
# ALTLinux CoreOS Cincinnati backend
[cincinnati]
base_url=\"http://getacos.altlinux.org\"
" > $MAIN_ROOT/etc/zincati/config.d/50-fedora-coreos-cincinnati.toml

echo "$UPDATEIP getacos.altlinux.org" >> $MAIN_ROOT/etc/hosts

chroot $MAIN_ROOT systemctl enable zincati.service


KERNEL=`find $MAIN_ROOT/boot/ -type f -name "vmlinuz-*"`
SHA=`sha256sum "$KERNEL" | awk '{print $1;}'`
mv "$KERNEL" "$KERNEL-$SHA"
rm -f $MAIN_ROOT/boot/vmlinuz
rm -f $MAIN_ROOT/boot/initrd*

cat <<EOF > $MAIN_ROOT/ostree.conf
d /run/ostree 0755 root root -
f /run/ostree/initramfs-mount-var 0755 root root -
EOF
chroot $MAIN_ROOT dracut --reproducible --gzip -v --no-hostonly \
	-f /boot/initramfs-$SHA \
	--add ignition --add ostree \
	--include /ostree.conf /etc/tmpfiles.d/ostree.conf \
	--include /etc/systemd/network/eth0.network /etc/systemd/network/eth0.network \
	--omit-drivers=floppy --omit=nfs --omit=lvm --omit=iscsi \
	--kver `ls $MAIN_ROOT/lib/modules`
rm -f $MAIN_ROOT/ostree.conf
rm -rf $MAIN_ROOT/usr/etc
mv $MAIN_ROOT/etc $MAIN_ROOT/usr/etc

tar -cf $VAR_ARCH -C $MAIN_ROOT var
rm -rf $MAIN_ROOT/var/*

if [ ! -d $MAIN_REPO ]
then
#Создание главного ostree-репозитория
	ostree init --repo=$MAIN_REPO --mode=bare
fi

ostree commit --repo=$MAIN_REPO --tree=dir=$MAIN_ROOT -b $BRANCH \
	--no-xattrs --no-bindings --mode-ro-executables \
	--add-metadata-string=version=sisyphus.$VERSION_DATE.0.0


rm -rf $TMP_DIR
