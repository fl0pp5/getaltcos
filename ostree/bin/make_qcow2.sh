#!/bin/sh
set -e
export DOCUMENT_ROOT=$(realpath `dirname $0`'/../../')
. $DOCUMENT_ROOT/ostree/bin/functions.sh


exec 2>&1
set -x

if [ $# -gt 4 ]
then
	echo "Help: $0 [<branch>] [<commitid> or <vardir>] [<directory of main ostree repository>] [<out_file>]"
	echo "For example: $0  acos/x86_64/sisyphus ac24e repo out/1.qcow2  "
	echo "For example: $0  acos/x86_64/sisyphus out/var repo out/1.qcow2  "
	echo "You can change TMPDIR environment variable to set another directory where temporary files will be stored"
	exit 1
fi

if [ `id -u` -ne 0 ]
then
        echo "ERROR: $0 needs to be run as root (uid=0) only"
        exit 1
fi

# Set brach variables
BRANCH=${1:-acos/x86_64/sisyphus}
BRANCHREPODIR=`refRepoDir $BRANCH`
BRANCH_REPO=$DOCUMENT_ROOT/ACOS/streams/$BRANCHREPODIR
MAIN_REPO=${3:-$BRANCH_REPO/bare/repo}
if [ ! -d $MAIN_REPO ]
then
	echo "ERROR: ostree repository must exist"
	exit 1
fi
BRANCHDIR=`refToDir $BRANCH`
BRANCH_DIR=$DOCUMENT_ROOT/ACOS/streams/$BRANCHDIR
if [ ! -d  $BRANCH_DIR ]
then
  mkdir -m 0775 -p  $BRANCH_DIR
fi

# Set Commit variables
SHORTCOMMITID=$2
if [ -z $SHORTCOMMITID ]
then
  COMMITID=`lastCommitId $BRANCHDIR`
  VAR_DIR=$BRANCH_REPO/vars/$COMMITID/var
else
  if [[ "$COMMITID" == */* ]] # It's VAR_DIR
  then
    VAR_DIR=$COMMITID
  else
    COMMITID=`fullCommitId $BRANCHDIR $SHORTCOMMITID`
    VAR_DIR=$BRANCH_DIR/vars/$COMMITID/var
  fi
fi
if [ -z "$COMMITID" ]
then
  echo "ERROR: Commit $SHORTCOMMITID must exist"
  exit 1
fi


OUT_FILE=$4
if [ -z "$OUT_FILE" ]
then
  IMAGE_DIR="$BRANCH_DIR/images"
  if [ ! -d $IMAGE_DIR ]
  then
    mkdir -m 0775 -p $IMAGE_DIR
  fi
  OUT_DIR="$IMAGE_DIR/qcow2"
  if [ ! -d $OUT_DIR ]
  then
    mkdir -m 0775 -p $OUT_DIR
  fi
  Outfile=`refVersion $BRANCH $COMMITID`
  OUT_FILE="$OUT_DIR/$Outfile.qcow2"
fi

OS_NAME=alt-containeros

MOUNT_DIR=`mktemp --tmpdir -d acos_make_qcow2-XXXXXX`
REPO_LOCAL=$MOUNT_DIR/ostree/repo
RAWFILE=`mktemp --tmpdir acos_make_qcow2-XXXXXX.raw`

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
ostree pull-local --repo $REPO_LOCAL $MAIN_REPO $BRANCH
grub-install --target=i386-pc --root-directory=$MOUNT_DIR $LOOPDEV
ln -s ../loader/grub.cfg $MOUNT_DIR/boot/grub/grub.cfg
ostree config --repo $REPO_LOCAL set sysroot.bootloader grub2
ostree refs --repo $REPO_LOCAL --create alt:$BRANCH $BRANCH
ostree admin os-init $OS_NAME --sysroot $MOUNT_DIR

OSTREE_BOOT_PARTITION="/boot" ostree admin deploy alt:$BRANCH --sysroot $MOUNT_DIR --os $OS_NAME \
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
# (
# xz -9v < $OUT_FILE > $OUT_FILE.xz
# ) &
