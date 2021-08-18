#!/bin/sh
set -x
exec 2>&1
ref=$1
lastCommitId=$2
version=$3
repoBarePath="$DOCUMENT_ROOT/ACOS/streams/$ref/bare/repo";
rootsPath="$DOCUMENT_ROOT/ACOS/streams/$ref/roots";

cd $rootsPath
newCommitId=`sudo ostree commit \
        --repo=$repoBarePath \
        --tree=dir=$lastCommitId \
        -b $ref  \
        --no-bindings \
        --mode-ro-executables \
        --add-metadata-string=version=$version
`
sudo ostree  summary --repo=$repoBarePath --update
mv $lastCommitId $newCommitId
ln -sf $newCommitId root
