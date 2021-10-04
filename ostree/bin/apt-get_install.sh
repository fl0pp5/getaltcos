#!/bin/sh
set -x
export DOCUMENT_ROOT=$(realpath `dirname $0`'/../../')
. $DOCUMENT_ROOT/ostree/bin/functions.sh

exec 2>&1
ref=$1
refDir=`refToDir $ref`
shift
rpms=$*
rootsPath="$DOCUMENT_ROOT/ALTCOS/streams/$refDir/roots";
checkAptDirs $rootsPath
sudo chroot $rootsPath/merged apt-get install -y $rpms
