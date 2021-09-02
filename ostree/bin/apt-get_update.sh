#!/bin/sh
set -x
exec 2>&1
ref=$1
rootsPath="$DOCUMENT_ROOT/ACOS/streams/$ref/roots";
sudo sed -i -e 's/#rpm \[alt\] http/rpm [alt] http/' $rootsPath/merged/usr/etc/apt/sources.list.d/alt.list
sudo apt-get update -o RPM::RootDir=$rootsPath/merged # -o Dir::Etc=$rootsPath/merged/usr/etc/apt;
