#!/bin/sh
set -x
exec 2>/tmp/apt0get_update.log
ref=$1
rootsPath="$DOCUMENT_ROOT/ACOS/streams/$ref/roots";
sudo apt-get update -o RPM::RootDir=$rootsPath/merged;
