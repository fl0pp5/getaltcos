#!/bin/sh
set -x
exec 2>&1
ref=$1
lastCommitId=$2
version=$3
clear=$4
repoBarePath="$DOCUMENT_ROOT/ACOS/streams/$ref/bare/repo";
rootsPath="$DOCUMENT_ROOT/ACOS/streams/$ref/roots"
rootsPathOld="$DOCUMENT_ROOT/ACOS/streams/$ref/rootsi.$$";


varSubDir=`echo $version | sed -e 's/\./\//g'`
#varFile=$DOCUMENT_ROOT/ACOS/install_archives/$ref/../$varSubDir/var.tar
varDir=$DOCUMENT_ROOT/ACOS/install_archives/$ref/../$varSubDir/var
if [ ! -d $varDir ]
then
  echo "var dicrectory $varDir don't exists"
  exit 1
fi

if [  "$clear" = 'all'  ]
then
  sudo mv $rootsPath $rootsPathOld 
  sudo umount $rootsPathOld/merged
  sudo  rm -rf $rootsPathOld
  sudo mkdir -p $rootsPath
fi
cd $rootsPath
if [ ! -d $lastCommitId ]
then
  sudo ostree checkout --repo $repoBarePath $lastCommitId
fi
sudo ln -sf $lastCommitId root

for dir in merged upper work
do 
  if [ -d $dir ];
  then
       sudo rm -rf $dir;
  fi;
  sudo mkdir $dir;
done
sudo mount -t overlay overlay -o lowerdir=$lastCommitId,upperdir=./upper,workdir=./work ./merged;

cd merged
sudo ln -sf  /usr/etc/ ./etc;
sudo rsync -av $varDir .
sudo mkdir -p ./run/lock ./run/systemd/resolve/ ./tmp/.private/root/
sudo cp /etc/resolv.conf .//run/systemd/resolve/resolv.conf

# sudo tar xvf $varFile
