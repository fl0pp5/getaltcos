#!/bin/sh

# Split passwd file (/etc/passwd) into
# /usr/etc/passwd - home users password file (uid >= 500)
# /usr/lib/passwd - system users password file (uid < 500)
splitPasswd() {
  > /usr/etc/passwd
  > /usr/lib/passwd
  set -f
  ifs=$IFS
  while read line
  do
    IFS=:;set -- $line;IFS=$ifs
    uid=$3
    if [ $uid -lt 500 ]
    then
      echo $line >> /usr/lib/passwd
    else
      echo $line >> /usr/etc/passwd
    fi
  done
}

# Split group file (/etc/group) into
# /usr/etc/group - home users password file (uid >= 500)
# /usr/lib/group - system users password file (uid < 500)
splitGroup() {
  > /usr/etc/group
  > /usr/lib/group
  set -f
  ifs=$IFS
  while read line
  do
    IFS=:;set -- $line;IFS=$ifs
    uid=$3
    if [ $uid -lt 500 ]
    then
      echo $line >> /usr/lib/group
    else
      echo $line >> /usr/etc/group
    fi
  done
}

# Возвращает тропу, где находятся репозитории bare, archive
# acos/x86_64/sisyphus -> acos/x86_64/sisyphus
# acos/x86_64/Sisyphus/apache -> acos/x86_64/sisyphus
refRepoDir() {
  ref=$1
  ifs=$IFS
  IFS=/;set -- $ref;IFS=$ifs;
  os=$1;arch=$2;branch=`echo $3 | tr '[:upper:]' '[:lower:]'`
  echo "$os/$arch/$branch";
}


# Возвращает иям поддиректория варианта в каталоге /vars
# sisyphus.20210914.0.0 => 20210914/0/0
# sisyphus_apache.20210914.0.0 => apache/20210914/0/0
versionVarSubDir() {
  version=$1
  ifs=$IFS
  IFS=.;set -- `echo $version | tr '[:upper:]' '[:lower:]'`;IFS=$ifs
  stream=$1
  major=$2
  minor=$3
  IFS=_;set -- $stream;IFS=$ifs
  shift; dir=$1; while $# -gt 1; do dir="$dir_$1";shift; done
  echo "$dir/$date/$major/$minor"
}





