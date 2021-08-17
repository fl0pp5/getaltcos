#!/bin/sh
set -x
exec 2>/tmp/apt-get_dist-upgrade.log
ref=$1
rootsPath="$DOCUMENT_ROOT/ACOS/streams/$ref/roots";
sudo apt-get dist-upgrade -y -o RPM::RootDir=$rootsPath/merged;
