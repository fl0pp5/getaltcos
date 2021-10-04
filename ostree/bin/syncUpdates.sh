#!/bin/sh
set -x
export DOCUMENT_ROOT=$(realpath `dirname $0`'/../../')
. $DOCUMENT_ROOT/ostree/bin/functions.sh
exec 2>&1
ref=$1
refDir=`refToDir $ref`
commitId=$2
version=$3

versionVarSubDir=`versionVarSubDir $version`
branchPath=$DOCUMENT_ROOT/ALTCOS/streams/$refDir/
rootsPath="$branchPath/roots";
commitPath="$rootsPath/root"
varDir="$branchPath/vars/$versionVarSubDir"

cd $rootsPath/;
sudo du -s upper
sudo du -s root/
sudo rm -f ./upper/etc ./root/etc;

sudo mkdir --mode 0775 -p $varDir
cd upper

# Clean RPM data
sudo rm -rf  ./var/lib/apt/ ./var/cache/apt
checkAptDirs $PWD
# sudo mkdir -p ./var/lib/apt/lists/ ./var/lib/apt/prefetch/ ./var/cache/apt/archives/partial
# sudo chmod -R 770 ./var/cache/apt/
# sudo chmod -R g+s ./var/cache/apt/
# sudo chown root:rpm ./var/cache/apt/

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

