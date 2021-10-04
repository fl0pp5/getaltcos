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
checkAptDirs $mergedDir
sudo chroot $mergedDir apt-get dist-upgrade -y
sudo chroot $mergedDir rpm -qa >  $rpmListFile
