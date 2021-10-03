#!/bin/sh
set -x

export DOCUMENT_ROOT=$(realpath `dirname $0`'/../../')
. $DOCUMENT_ROOT/ostree/bin/functions.sh

exec 2>&1
ref=$1
lastCommitId=$2
toRef=$3
clear=$4

refRepoDir=`refRepoDir $ref`
branchRepoPath="$DOCUMENT_ROOT/ALTCOS/streams/$refRepoDir"
repoBarePath="$branchRepoPath/bare/repo";

refDir=`refToDir $ref`
branchPath="$DOCUMENT_ROOT/ALTCOS/streams/$refDir"
varDir="$branchPath/vars/$lastCommitId"
if [ -n "$toRef" ]
then
  refToDir=`refToDir $toRef`
  branchPath="$DOCUMENT_ROOT/ALTCOS/streams/$refToDir"
fi
rootsPath="$branchPath/roots"
rootsPathOld="$branchPath/rootsi.$$";

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

# Гарантировать размонтирование merged
while sudo umount ./merged; do :; done

# Пересоздать каталоги
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

