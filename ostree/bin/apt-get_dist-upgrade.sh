#!/bin/sh
set -x
#exec 2>/tmp/apt-get_dist-upgrade.log
exec 2>&1
ref=$1
rootsPath="$DOCUMENT_ROOT/ACOS/streams/$ref/roots";
sudo chroot $rootsPath/merged apt-get dist-upgrade -y #-o RPM::RootDir=$rootsPath/merged  2>/tmp/dist-upgrade.log

