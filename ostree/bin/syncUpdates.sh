#!/bin/sh
set -x
. $DOCUMENT_ROOT/ostree/bin/functions.sh
exec 2>&1
ref=$1
refDir=`refToDir $ref`
commitId=$2

branchPath=$DOCUMENT_ROOT/ACOS/streams/$refDir/
rootsPath="$branchPath/roots";
commitPath="$rootsPath/root"
varDir="$branchPath/vars/$commitId"

cd $rootsPath/;
sudo du -s upper
sudo du -s root/
sudo rm -f ./upper/etc ./root/etc;

sudo mkdir --mode 0775 -p $varDir
cd upper
sudo rsync -av var $varDir
sudo rm -rf ./var ./run
sudo mkdir ./var
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

