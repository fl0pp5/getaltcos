#!/bin/sh
set -x
exec 2>&1
date
export DOCUMENT_ROOT=$(realpath `dirname $0`'/../../')
. $DOCUMENT_ROOT/ostree/bin/functions.sh

mergeDir=$1
shift
dockerImagesDir="$mergeDir/usr/dockerImages"
if [ ! -d $dockerImagesDir ]
then
  sudo mkdir -p $dockerImagesDir
fi
tmpfile="/tmp/skopeo.$$"
for image
do
  archiveFile=`echo $image | tr '/' '_'| tr ':' '_'`
  archiveFile="$dockerImagesDir/$archiveFile"
  sudo rm -f $archiveFile
  xzfile="$archiveFile.xz";
  if [ ! -f $xzfile ]
  then
    >$tmpfile
    until grep manifest $tmpfile
    do
      sudo rm -f $archiveFile
      sudo skopeo copy --additional-tag=$image docker://$image docker-archive:$archiveFile  2>&1 | tee $tmpfile
    done
    sudo xz -9 $archiveFile
  fi
done
rm -f $tmpfile
date
