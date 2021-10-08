#!/bin/sh
set -e
export DOCUMENT_ROOT=$(realpath `dirname $0`'/../../')
. $DOCUMENT_ROOT/ostree/bin/functions.sh


if [ $# = 0 ]
then
	echo "Help: $0 <branch> [<rootfs archive>] [<directory of main ostree repository>] [<directory for output archives>]"
	echo "For example: $0 altcos/x86_64/sisyphus out/altcos-20210824-x86_64.tar repo out"
	echo "You can change TMPDIR environment variable to set another directory where temporary files will be stored"
	echo "If directory of main ostree repository doesn't exists, new repository will be created"
	echo "Default values:"
	echo "- <rootfs archive> \$DOCUMENT_ROOT/ALTCOS/rootfs_archives/<branch>/altcos-latest-x86_64.tar"
	echo "- <directory of main ostree repository> \$DOCUMENT_ROOT/ALTCOS/streams/<branch>/bare"
	echo "- <directory for output archives> \$DOCUMENT_ROOT/ALTCOS/streams/<branch>/vars"
	exit 1
fi

if [ `id -u` -ne 0 ]
then
        echo "ERROR: $0 needs to be run as root (uid=0) only"
        exit 1
fi

BRANCH=${1:-altcos/x86_64/sisyphus}
BRANCH_REPO=$DOCUMENT_ROOT/ALTCOS/streams/$BRANCH
ROOTFS_ARCHIVE="${2:-$BRANCH_REPO/mkimage-profiles/altcos-latest-x86_64.tar}"
MAIN_REPO="${3:-$BRANCH_REPO/bare/repo}"
OUT_DIR="${4:-$BRANCH_REPO/vars}"

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
	echo "ERROR: The name of the rootfs archive ($ROOTFS_ARCHIVE) contains an incorrect date"
	exit 1
fi

DATA_DIR=${OUT_DIR}/$VERSION_DATE
if [ -d $DATA_DIR  -a -n "`ls -1 $DATA_DIR`" ]
then
  let MAJOR=`ls -1 $DATA_DIR | sort -n | tail -1`+1
else
  MAJOR=0
fi


VERSION_DIR=$VERSION_DATE/$MAJOR/0
VERSION_FULLDIR=${OUT_DIR}/$VERSION_DIR

if [ -d $VERSION_FULLDIR ]
then
	echo "ERROR: Version for date $VERSION_DATE already exists."
	echo "Try: rm -rf $VERSION_FULLDIR"
	exit 1
fi
rm -rf $VERSION_FULLDIR

mkdir --mode=0775 -p $VERSION_FULLDIR

TMP_DIR=`mktemp --tmpdir -d rootfs_to_repo-XXXXXX`
MAIN_ROOT=$TMP_DIR/root

mkdir --mode=0775 -p $MAIN_ROOT
tar xf $ROOTFS_ARCHIVE -C $MAIN_ROOT --exclude=./dev/tty --exclude=./dev/tty0 --exclude=./dev/console  --exclude=./dev/urandom --exclude=./dev/random --exclude=./dev/full --exclude=./dev/zero --exclude=/dev/null --exclude=./dev/pts/ptmx --exclude=./dev/null

#Вынести в m-i-p
rm -f $MAIN_ROOT/etc/resolv.conf
ln -sf /run/systemd/resolve/resolv.conf $MAIN_ROOT/etc/resolv.conf

RPMS_DIR="/home/$SUDO_USER/apt/$BRANCH"
if [ -d $RPMS_DIR ]
then
  apt-get update -y -o RPM::RootDir=$MAIN_ROOT
  apt-get install -y -o RPM::RootDir=$MAIN_ROOT $RPMS_DIR/*
fi

sed -i 's/^LABEL=ROOT\t/LABEL=boot\t/g' $MAIN_ROOT/etc/fstab
sed -i 's/^AcceptEnv /#AcceptEnv /g' $MAIN_ROOT/etc/openssh/sshd_config
sed -i 's/^# WHEEL_USERS ALL=(ALL) ALL$/WHEEL_USERS ALL=(ALL) ALL/g' $MAIN_ROOT/etc/sudoers
echo "zincati ALL=NOPASSWD: ALL" > $MAIN_ROOT/etc/sudoers.d/zincati
sed -i 's|^HOME=/home$|HOME=/var/home|g' $MAIN_ROOT/etc/default/useradd
echo "blacklist floppy" > $MAIN_ROOT/etc/modprobe.d/blacklist-floppy.conf
mkdir --mode=0775 $MAIN_ROOT/sysroot
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
rsync -avd $MAIN_ROOT/var/lib/rpm $MAIN_ROOT/usr/share/

mkdir --mode=0775 -p $MAIN_ROOT/etc/ostree/remotes.d/
echo "
[remote \"altcos\"]
url=https://altcos.altlinux.org/ALTCOS/streams/$BRANCH/archive/repo/
gpg-verify=false
" > $MAIN_ROOT/etc/ostree/remotes.d/altcos.conf
echo "
# ALTLinux CoreOS Cincinnati backend
[cincinnati]
base_url=\"https://altcos.altlinux.org\"
" > $MAIN_ROOT/etc/zincati/config.d/50-altcos-cincinnati.toml
echo "
[Match]
Name=eth0

[Network]
DHCP=yes
" > $MAIN_ROOT/etc/systemd/network/20-wired.network

echo "$UPDATEIP getaltcos.altlinux.org" >> $MAIN_ROOT/etc/hosts

chroot $MAIN_ROOT groupadd altcos
chroot $MAIN_ROOT useradd -g altcos -G docker,wheel -d /var/home/altcos --create-home -s /bin/bash altcos

# Split passwd file (/etc/passwd) into
splitPasswd $MAIN_ROOT/etc/passwd $MAIN_ROOT/lib/passwd /tmp/passwd.$$
mv /tmp/passwd.$$ $MAIN_ROOT/etc/passwd
#
# # Split group file (/etc/group)
splitGroup $MAIN_ROOT/etc/group $MAIN_ROOT/lib/group /tmp/group.$$
mv /tmp/group.$$ $MAIN_ROOT/etc/group

sed -e 's/passwd:.*$/& altfiles/' -e 's/group.*$/& altfiles/' -i $MAIN_ROOT/etc/nsswitch.conf

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
	--add ignition-altcos --add ostree \
	--include /ostree.conf /etc/tmpfiles.d/ostree.conf \
	--include /etc/systemd/network/eth0.network /etc/systemd/network/eth0.network \
	--omit-drivers=floppy --omit=nfs --omit=lvm --omit=iscsi \
	--kver `ls $MAIN_ROOT/lib/modules`

rm -f $MAIN_ROOT/ostree.conf
rm -rf $MAIN_ROOT/usr/etc
mv $MAIN_ROOT/etc $MAIN_ROOT/usr/etc

rsync -av $MAIN_ROOT/var $VERSION_FULLDIR

# tar -cf $VAR_ARCH -C $MAIN_ROOT var
rm -rf $MAIN_ROOT/var
mkdir $MAIN_ROOT/var

if [ ! -d $MAIN_REPO ]
then
#Создание главного ostree-репозитория
	mkdir --mode=0775 -p $MAIN_REPO
	ostree init --repo=$MAIN_REPO --mode=bare
fi

COMMITID=`ostree commit --repo=$MAIN_REPO --tree=dir=$MAIN_ROOT -b $BRANCH \
	--no-xattrs --no-bindings --mode-ro-executables \
	--add-metadata-string=version=sisyphus.$VERSION_DATE.$MAJOR.0`

cd ${OUT_DIR}
ln -sf $VERSION_DIR $COMMITID
rm -rf $TMP_DIR
