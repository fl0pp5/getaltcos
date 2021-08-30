#!/bin/bash
set -e

if [ $# != 2 ] 
then
	echo "Help: $0 <device to install> <ignition configuration file>"
	echo "For example: $0 /dev/sda /usr/share/acos/config_example.ign"
	exit 1
fi

DEVICE=$1
IGNITION_CONFIG=$2
BRANCH=acos/x86_64/sisyphus
OS_NAME=alt-containeros
MOUNT_DIR=/tmp/acos
REPO_LOCAL=$MOUNT_DIR/ostree/repo
ARCHIVE_DIR=/usr/share/acos
STEP_COLOR='\033[1;32m'
WARN_COLOR='\033[1;31m'
NO_COLOR='\033[0m'

if [ ! -b $DEVICE ]
then
	echo "The first argument must be a file name of block device"
	exit 1
fi

if [ ! -f $IGNITION_CONFIG ]
then
	echo "The second argument must be an existing file name"
	exit 1
fi

if [ `id -u` -ne 0 ]
then
	echo "Script $0 can be run by root(uid=0) only"
	exit 1
fi

set +e
mount|grep ^$DEVICE
if [ "$?" -eq 0 ]
then
	echo "The disk on which the installation is being performed must be unmounted"
	exit 1
fi
set -e

parted $DEVICE print
[ "$?" -ne 0 ] && exit 1

echo -en "${WARN_COLOR}All data on the disk will be destroyed.${NO_COLOR} "
read -p "Are you sure you want to install ACOS on this disk (y/n)? " -n 1 -r
echo
[[ $REPLY =~ ^[Yy]$ ]] || exit 1 


echo -e "${STEP_COLOR}*** Creating a partition and file system ***${NO_COLOR}"
dd if=/dev/zero of=$DEVICE bs=1M count=3
parted $DEVICE mktable msdos 2>&1 | grep -v /etc/fstab
parted -a optimal $DEVICE mkpart primary ext4 2MIB 100% 2>&1 | grep -v /etc/fstab
parted $DEVICE set 1 boot on 2>&1 | grep -v /etc/fstab
#label "boot" is required for ignition to find partition.
mkfs.ext4 -L boot "$DEVICE"1
mkdir $MOUNT_DIR
mount "$DEVICE"1 $MOUNT_DIR

echo -e "${STEP_COLOR}*** Unpacking ostree repository ***${NO_COLOR}"
tar xf $ARCHIVE_DIR/acos_root.tar.xz -C $MOUNT_DIR

echo -e "${STEP_COLOR}*** GRUB installation ***${NO_COLOR}"
grub-install --root-directory=$MOUNT_DIR $DEVICE
ln -s ../loader/grub.cfg $MOUNT_DIR/boot/grub/grub.cfg

echo -e "${STEP_COLOR}*** Deployment of $OS_NAME ***${NO_COLOR}"
ostree config --repo $REPO_LOCAL set sysroot.bootloader grub2
ostree refs --repo $REPO_LOCAL --create alt:$BRANCH $BRANCH
ostree admin os-init $OS_NAME --sysroot $MOUNT_DIR
OSTREE_BOOT_PARTITION="/boot" ostree admin deploy alt:$BRANCH --sysroot $MOUNT_DIR --os $OS_NAME \
	--karg-append=ignition.platform.id=metal --karg-append=\$ignition_firstboot \
	--karg-append=net.ifnames=0 --karg-append=biosdevname=0 \
	--karg-append=quiet --karg-append=root=UUID=`blkid --match-tag UUID -o value "$DEVICE"1`

echo -e "${STEP_COLOR}*** Filling in /var directory ***${NO_COLOR}"
rm -rf $MOUNT_DIR/ostree/deploy/$OS_NAME/var
tar xf $ARCHIVE_DIR/var.tar.xz -C $MOUNT_DIR/ostree/deploy/$OS_NAME/
touch $MOUNT_DIR/ostree/deploy/$OS_NAME/var/.ostree-selabeled

echo -e "${STEP_COLOR}*** Creating files for ignition ***${NO_COLOR}"
mkdir $MOUNT_DIR/ignition
cp $IGNITION_CONFIG $MOUNT_DIR/ignition/config.ign
touch $MOUNT_DIR/boot/ignition.firstboot

echo
echo -e "${STEP_COLOR}*** Setting root password ***${NO_COLOR}"
chroot $MOUNT_DIR/ostree/boot.1/$OS_NAME/*/0/ passwd

echo -e "${STEP_COLOR}*** Unmounting ***${NO_COLOR}"
umount $MOUNT_DIR
rm -r $MOUNT_DIR

echo -e "${STEP_COLOR}*** ACOS has been successfully installed ***${NO_COLOR}"

