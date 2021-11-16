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
for image
do
  archiveFile=`echo $image | tr '/' '_'| tr ':' '_'`
  archiveFile="$dockerImagesDir/$archiveFile"
  sudo skopeo copy --additional-tag=$image docker://$image docker-archive:$archiveFile
  sudo xz -9 $archiveFile
done
date
