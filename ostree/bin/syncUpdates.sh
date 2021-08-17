#!/bin/sh
set -x
exec 2>/tmp/sync_updates.log
ref=$1
rootsPath="$DOCUMENT_ROOT/ACOS/streams/$ref/roots";
commitPath="$rootsPath/root"
cd $rootsPath/;
sudo rm -f ./upper/etc;
#ls -l ./merged;
echo 'UPPER=';ls -lR ./upper;
cd upper
delete=`find . -type c`;
echo DELETE $delete
sudo rm -rf $delete
cd ../$lastCommitId/;
sudo rm -rf $delete
cd ../upper;
#echo 'RM==='
#find . -type c -exec echo sudo rm -rf  $commitPath/{} 2>&1\;
echo 'CPIO===';
find . -depth | sudo cpio -plmvdu $commitPath/ 2>&1;
cd ..
