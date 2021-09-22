#!/bin/sh
set -x

. $DOCUMENT_ROOT/ostree/bin/functions.sh

exec 2>&1
ref=$1
lastCommitId=$2
clear=$3

versionVarSubDir=`versionVarSubDir $ref`
refRepoDir=`repos::refRepoDir $ref`
refDir=`repos::refToDir $ref`
branchRepoPath="$DOCUMENT_ROOT/ACOS/streams/$refRepoDir"
branchPath="$DOCUMENT_ROOT/ACOS/streams/$ref"
repoBarePath="$branchRepoPath/bare/repo";
rootsPath="$branchPath/roots"
rootsPathOld="$branchPath/rootsi.$$";
varDir="$branchPath/vars/$versionVarSubDir"

if [ ! -d $varDir ]
then
  echo "var directory $varDir don't exists"
  exit 1
fi

if [  "$clear" = 'all'  ]
then
  sudo mv $rootsPath $rootsPathOld
  sudo umount $rootsPathOld/merged
  sudo  rm -rf $rootsPathOld
fi
sudo mkdir -p $rootsPath

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
sudo rsync -av $varDir/var .
sudo mkdir -p ./run/lock ./run/systemd/resolve/ ./tmp/.private/root/
sudo cp /etc/resolv.conf ./run/systemd/resolve/resolv.conf

