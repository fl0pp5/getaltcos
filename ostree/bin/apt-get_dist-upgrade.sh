#!/bin/sh
set -x
#exec 2>/tmp/apt-get_dist-upgrade.log
exec 2>&1
ref=$1
rpmListFile=$2
rootsPath="$DOCUMENT_ROOT/ACOS/streams/$ref/roots";
sudo chroot $rootsPath/merged apt-get dist-upgrade -y
sudo chroot $rootsPath/merged rpm -qa >  $rpmListFile
