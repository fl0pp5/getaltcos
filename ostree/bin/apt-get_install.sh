#!/bin/sh
set -x
exec 2>&1
ref=$1
shift
rpms=$*
rootsPath="$DOCUMENT_ROOT/ACOS/streams/$ref/roots";
sudo chroot $rootsPath/merged apt-get install -y $rpms
