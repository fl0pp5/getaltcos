#!/bin/sh
set -x
exec 2>&1
ref=$1
version=$2
branchPath=$DOCUMENT_ROOT/ACOS/streams/$ref/
rootsPath="$branchPath/roots";
commitPath="$rootsPath/root"
ifs=$IFS; IFS=.;set -- $version;IFS=$ifs;shift;varSubDir="vars/$1/$2/$3"
varDir=$branchPath/$varSubDir

cd $rootsPath/;
sudo du -s upper
sudo du -s root/
sudo rm -f ./upper/etc ./root/etc;

sudo mkdir -p $varDir
cd upper
sudo rsync -av var $varDir
sudo rm -rf ./var ./run
delete=`sudo find . -type c`;
echo DELETE $delete
sudo rm -rf $delete
cd $commitPath
sudo rm -rf $delete
cd ../upper;
#echo 'RM==='
#find . -type c -exec echo sudo rm -rf  $commitPath/{} 2>&1\;
sudo find . -depth | (cd ../merged;sudo cpio -plmdu $commitPath/) 2>&1;

cd ..
sudo du -s upper
sudo du -s root/
sudo umount merged

