#!/bin/sh
set -x
. $DOCUMENT_ROOT/ostree/bin/functions.sh

exec 2>&1
ref=$1
refDir=`repos::refToDir $ref`

rpmListFile=$2
rootsPath="$DOCUMENT_ROOT/ACOS/streams/$refDir/roots";
sudo chroot $rootsPath/merged apt-get dist-upgrade -y
sudo chroot $rootsPath/merged rpm -qa >  $rpmListFile
