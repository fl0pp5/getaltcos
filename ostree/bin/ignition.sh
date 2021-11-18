#!/bin/sh
set -x
exec 2>&1
date
export DOCUMENT_ROOT=$(realpath `dirname $0`'/../../')
. $DOCUMENT_ROOT/ostree/bin/functions.sh

refDir=$1
butaneFile=$2

rootDir=$refDir/roots/merged
ignFile="$refDir/$butaneFile.jgn"

sudo butane -p -d $refDir $refDir/$butaneFile | sudo tee $ignFile

sudo /usr/lib/dracut/modules.d/30ignition/ignition  \
  -platform file \
  --stage files \
  -config-cache $ignFile \
  -root $rootDir

sudo chroot $rootDir systemctl preset-all --preset-mode=enable-only
