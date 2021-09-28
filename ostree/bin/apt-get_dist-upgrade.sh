#!/bin/sh
set -x
export DOCUMENT_ROOT=$(realpath `dirname $0`'/../../')
. $DOCUMENT_ROOT/ostree/bin/functions.sh

exec 2>&1
ref=$1
refDir=`refToDir $ref`

rpmListFile=$2
rootsPath="$DOCUMENT_ROOT/ALTCOS/streams/$refDir/roots";
sudo chroot $rootsPath/merged apt-get dist-upgrade -y
sudo chroot $rootsPath/merged rpm -qa >  $rpmListFile
