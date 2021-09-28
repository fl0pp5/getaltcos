#!/bin/sh
set -e

if [ $# -lt 2 ]
then
	echo "Help: $0 <output file> <var directory> [<branch>] [<directory of main ostree repository>]"
	echo "For example: $0 out/1.qcow2 out/20210917/0/0/var altcos/x86_64/sisyphus repo"
	echo "You can change TMPDIR environment variable to set another directory where temporary files will be stored"
	exit 1
fi

if [ `id -u` -ne 0 ]
then
        echo "ERROR: $0 needs to be run as root (uid=0) only"
        exit 1
fi

OS_NAME=alt-containeros
OUT_FILE=$1
VAR_DIR=$2
if [ ! -d $VAR_DIR ]
then
	echo "ERROR: var directory must exist"
	exit 1
fi

OSTREE_BRANCH=${3:-altcos/x86_64/sisyphus}
BRANCH_REPO=$DOCUMENT_ROOT/ALTCOS/streams/$OSTREE_BRANCH
MAIN_REPO="${4:-$BRANCH_REPO/bare/repo}"
if [ ! -d $MAIN_REPO ]
then
	echo "ERROR: ostree repository must exist"
	exit 1
fi


MOUNT_DIR=`mktemp --tmpdir -d altcos_make_qcow2-XXXXXX`
REPO_LOCAL=$MOUNT_DIR/ostree/repo
RAWFILE=`mktemp --tmpdir altcos_make_qcow2-XXXXXX.raw`

fallocate -l 3GiB $RAWFILE

LOOPDEV=`losetup --show -f $RAWFILE`
LOOPPART="$LOOPDEV"p1

dd if=/dev/zero of=$LOOPDEV bs=1M count=3
parted $LOOPDEV mktable msdos
parted -a optimal $LOOPDEV mkpart primary ext4 2MIB 100%
parted $LOOPDEV set 1 boot on
mkfs.ext4 -L boot $LOOPPART

mount $LOOPPART $MOUNT_DIR
ostree admin init-fs --modern $MOUNT_DIR
ostree pull-local --repo $REPO_LOCAL $MAIN_REPO $OSTREE_BRANCH
grub-install --target=i386-pc --root-directory=$MOUNT_DIR $LOOPDEV
ln -s ../loader/grub.cfg $MOUNT_DIR/boot/grub/grub.cfg
ostree config --repo $REPO_LOCAL set sysroot.bootloader grub2
ostree refs --repo $REPO_LOCAL --create alt:$OSTREE_BRANCH $OSTREE_BRANCH
ostree admin os-init $OS_NAME --sysroot $MOUNT_DIR

OSTREE_BOOT_PARTITION="/boot" ostree admin deploy alt:$OSTREE_BRANCH --sysroot $MOUNT_DIR --os $OS_NAME \
	--karg-append=ignition.platform.id=qemu --karg-append=\$ignition_firstboot \
	--karg-append=net.ifnames=0 --karg-append=biosdevname=0 \
	--karg-append=quiet --karg-append=root=UUID=`blkid --match-tag UUID -o value $LOOPPART`

rm -rf $MOUNT_DIR/ostree/deploy/$OS_NAME/var
rsync -av $VAR_DIR $MOUNT_DIR/ostree/deploy/$OS_NAME/
touch $MOUNT_DIR/ostree/deploy/$OS_NAME/var/.ostree-selabeled

touch $MOUNT_DIR/boot/ignition.firstboot

umount $MOUNT_DIR
rm -rf $MOUNT_DIR
losetup --detach "$LOOPDEV"
qemu-img convert -O qcow2 $RAWFILE $OUT_FILE
rm $RAWFILE
