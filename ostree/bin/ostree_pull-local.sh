#!/bin/sh
set -x
exec 2>&1

repoBarePath="$DOCUMENT_ROOT/ACOS/streams/$ref/bare/repo";
repoArchivePath="$DOCUMENT_ROOT/ACOS/streams/$ref/archive/repo";

sudo ostree pull-local --repo $repoArchivePath $repoArchivePath
sudo ostree  summary --repo=$repoArchivePath --update

