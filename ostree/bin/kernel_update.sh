#!/bin/sh
set -x
exec 2>&1

export DOCUMENT_ROOT=$(realpath `dirname $0`'/../../')
. $DOCUMENT_ROOT/ostree/bin/functions.sh

ref=$1
refDir=`refToDir $ref`

rpmListFile=$2
rootsPath="$DOCUMENT_ROOT/ALTCOS/streams/$refDir/roots";
mergedDir=$rootsPath/merged
sudo chroot $mergedDir rm -rf /var/lib/rpm
sudo chroot $mergedDir ln -sf /lib/rpm/ /var/lib/
sudo chroot $mergedDir apt-get install -y update-kernel
sudo chroot $mergedDir update-kernel -y
sudo chroot $mergedDir apt-get remove -y update-kernel
