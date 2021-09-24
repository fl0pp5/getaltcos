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

repoBarePath="$DOCUMENT_ROOT/ACOS/streams/$refRepoDir/bare/repo";
rootsPath="$DOCUMENT_ROOT/ACOS/streams/$refDir/roots";
varsPath="$DOCUMENT_ROOT/ACOS/streams/$refDir/vars";

cd $rootsPath
newCommitId=`sudo ostree commit \
        --repo=$repoBarePath \
        --tree=dir=$commitId \
        -b $ref  \
        --no-bindings \
        --mode-ro-executables \
        --add-metadata-string=version=$nextVersion
`
sudo ostree  summary --repo=$repoBarePath --update
sudo mv $commitId $newCommitId
sudo ln -sf $newCommitId root
cd $varsPath
sudo ln -sf $nextVersionVarSubDir $newCommitId
