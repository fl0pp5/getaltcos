#!/bin/sh
export DOCUMENT_ROOT=$(realpath `dirname $0`'/../../')

if [ $UID -eq 0 ]
then
	echo "Can't run as superuser "
	exit 1
fi

if [ $# -ne 1 -o "$1" != 'sisyphus' -a "$1" != 'p10' ]
then
	echo "Format: $0 sisyphus|p10"
	exit 1
fi
BRANCH=$1

if [ $BRANCH = 'sisyphus' ]
then
	REPOBRANCH='Sisyphus'
	NS=alt
else
	REPOBRANCH=$BRANCH/branch
	NS=$BRANCH
fi

ref=altcos/x86_64/$BRANCH

if [ -z "$DOCUMENT_ROOT" ]
then
	echo "Variable DOCUMENT_ROOT must be defined"
	exit 1
fi
BRANCH_REPO=$DOCUMENT_ROOT/ALTCOS/streams/$ref
export IMAGEDIR="$BRANCH_REPO/mkimage-profiles"
sudo mkdir -p $IMAGEDIR
sudo chmod 777 $IMAGEDIR

if [ ! -d $IMAGEDIR ]
then
  sudo mkdir -p $IMAGEDIR
fi

MKIMAGEDIR="$DOCUMENT_ROOT/../mkimage-profiles"
if [ ! -d $MKIMAGEDIR ]
then
        echo "mkimage-profiles directory $MKIMAGEDIR must exists"
        exit 1
fi

APTDIR="$HOME/apt"
if [ ! $APTDIR ]
then
	mkdir -p $APTDIR
fi
if [ ! -f  $APTDIR/lists/partial ]
then
	mkdir -p $APTDIR/lists/partial
fi
if [ ! -f $APTDIR/cache/$BRANCH/archives/partial ]
then
	mkdir -p $APTDIR/cache/$BRANCH/archives/partial
fi
if [ ! -d $APTDIR/x86_64/RPMS.dir ]
then
	mkdir -p $APTDIR/x86_64/RPMS.dir
fi
cat <<EOF > $APTDIR/apt.conf.$BRANCH.x86_64
Dir::Etc::SourceList "$APTDIR/sources.list.$BRANCH.x86_64";
Dir::Etc::SourceParts /var/empty;
Dir::Etc::main "/dev/null";
Dir::Etc::parts "/var/empty";
APT::Architecture "64";
Dir::State::lists "$APTDIR/lists/";
Dir::Cache "$APTDIR/cache/$BRANCH/";
EOF

cat <<EOF > $APTDIR/sources.list.$BRANCH.x86_64
rpm [$NS] http://ftp.altlinux.org/pub/distributions/ALTLinux/ $REPOBRANCH/x86_64 classic
rpm [$NS] http://ftp.altlinux.org/pub/distributions/ALTLinux/ $REPOBRANCH/noarch classic
rpm-dir file:$APTDIR x86_64 dir
EOF

cd $MKIMAGEDIR

make DEBUG=1 APTCONF=$APTDIR/apt.conf.$BRANCH.x86_64 BRANCH=$BRANCH ARCH=x86_64 vm/altcos.tar
