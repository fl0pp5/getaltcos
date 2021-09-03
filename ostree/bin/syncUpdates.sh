#!/bin/sh
set -x
exec 2>&1
ref=$1
version=$2
rootsPath="$DOCUMENT_ROOT/ACOS/streams/$ref/roots";
commitPath="$rootsPath/root"
varSubDir=`echo $version | sed -e 's/\./\//g'`
varDir=$DOCUMENT_ROOT/ACOS/install_archives/$ref/../$varSubDir/
# varFile=$varDir/var.tar

cd $rootsPath/;
sudo umount merged
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
echo 'CPIO===';
sudo find . -depth | sudo cpio -plmdu $commitPath/ 2>&1;
cd ..
sudo du -s upper
sudo du -s root/

