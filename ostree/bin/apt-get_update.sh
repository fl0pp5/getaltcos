#!/bin/sh
set -x
exec 2>&1

export DOCUMENT_ROOT=$(realpath `dirname $0`'/../../')
. $DOCUMENT_ROOT/ostree/bin/functions.sh

ref=$1
refDir=`refToDir $ref`

rootsPath="$DOCUMENT_ROOT/ALTCOS/streams/$refDir/roots";
mergedDir=$rootsPath/merged
checkAptDirs $mergedDir
sudo sed -i -e 's/#rpm \[alt\] http/rpm [alt] http/' $mergedDir/usr/etc/apt/sources.list.d/alt.list
sudo chroot $mergedDir  apt-get update

