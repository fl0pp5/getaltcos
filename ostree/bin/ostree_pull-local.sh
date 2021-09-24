#!/bin/sh
set -x
exec 2>&1
export DOCUMENT_ROOT=$(realpath `dirname $0`'/../../')
ref=$1
repoBarePath="$DOCUMENT_ROOT/ACOS/streams/$ref/bare/repo";
repoArchivePath="$DOCUMENT_ROOT/ACOS/streams/$ref/archive/repo";

if [ ! -d $repoArchivePath ]
then
  sudo mkdir -p  $repoArchivePath
  sudo ostree init --repo=$repoArchivePath --mode=archive
fi
sudo ostree pull-local --repo $repoArchivePath $repoBarePath $ref
sudo ostree  summary --repo=$repoArchivePath --update

