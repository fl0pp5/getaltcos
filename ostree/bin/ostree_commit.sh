#!/bin/sh
set -x
export DOCUMENT_ROOT=$(realpath `dirname $0`'/../../')
. $DOCUMENT_ROOT/ostree/bin/functions.sh
exec 2>&1

ref=$1
refRepoDir=`refRepoDir $ref`
refDir=`refToDir $ref`

commitId=$2
version=`refVersion $ref $commitId`

nextVersion=$3
nextVersionVarSubDir=`versionVarSubDir $nextVersion`

repoBarePath="$DOCUMENT_ROOT/ALTCOS/streams/$refRepoDir/bare/repo";
RefDir=$DOCUMENT_ROOT/ALTCOS/streams/$refDir
rootsPath="$RefDir/roots";
varsPath="$RefDir/vars";

addMetaData=
if ! isBaseRef $ref
then
  ALTCOSfile="$RefDir/ALTCOSfile.yml"
  BUTANEfile="$RefDir/BUTANEfile.yml"
  addMetaData=" --add-metadata-string=parentCommitId=$commitId"
  addMetaData="$addMetaData --add-metadata-string=parentVersion=$version"
  ALTCOSfileModTime=`date -r $ALTCOSfile +%s 2>/dev/null`
  addMetaData="$addMetaData --add-metadata-string=ALTCOSfileModTime=$ALTCOSfileModTime"
  if [ -f "$BUTANEfile" ]
  then
    butanefileModTime=`date -r $BUTANEfile +%s 2>/dev/null`
    addMetaData="$addMetaData --add-metadata-string=butanefileModTime=$butanefileModTime"
  fi

fi
cd $rootsPath
newCommitId=`sudo ostree commit \
        --repo=$repoBarePath \
        --tree=dir=$commitId \
        -b $ref  \
        --no-bindings \
        --mode-ro-executables \
        $addMetaData \
        --add-metadata-string=version=$nextVersion
`
sudo ostree  summary --repo=$repoBarePath --update

sudo rm -rf $commitId
# sudo mv $commitId $newCommitId
# sudo ln -sf $newCommitId root
cd $varsPath
sudo ln -sf $nextVersionVarSubDir $newCommitId
echo $newCommitId
