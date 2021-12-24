#!/bin/sh
set -x
exec 2>&1
date
export DOCUMENT_ROOT=$(realpath `dirname $0`'/../../')
. $DOCUMENT_ROOT/ostree/bin/functions.sh

refDir=$1
rootDir=$2
btnFile="/tmp/$$.btn"
ignFile="/tmp/$$.ign"
sudo cat > $btnFile
sudo butane -p -d $refDir $btnFile | sudo tee $ignFile

sudo /usr/lib/dracut/modules.d/30ignition/ignition  \
  -platform file \
  --stage files \
  -config-cache $ignFile \
  -root $rootDir

# sudo rm -f  $ignFile $btnFile

sudo chroot $rootDir systemctl preset-all --preset-mode=enable-only
