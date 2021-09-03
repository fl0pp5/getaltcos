#!/bin/sh
set -x
exec 2>&1
ref=$1
rootsPath="$DOCUMENT_ROOT/ACOS/streams/$ref/roots";
sudo sed -i -e 's/#rpm \[alt\] http/rpm [alt] http/' $rootsPath/merged/usr/etc/apt/sources.list.d/alt.list
# sudo md5sum $rootsPath/merged/var/lib/rpm/*
sudo chroot $rootsPath/merged  apt-get update #-o RPM::RootDir=$rootsPath/merged # -o Dir::Etc=$rootsPath/merged/usr/etc/apt;
# sudo md5sum $rootsPath/merged/var/lib/rpm/*

