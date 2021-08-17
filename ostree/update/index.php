<?php
function cmpByVersion($commit1, $commit2) {
  $ret = strcmp($commit2['Version:'], $commit1['Version:']);
  return $ret;
}


//MAIN
$ref = $_REQUEST['ref'];
$version = $_REQUEST['version'];

$repoBarePath = "/var/www/vhosts/getacos/ACOS/streams/$ref/bare/repo";
$repoArchivePath = "/var/www/vhosts/getacos/ACOS/streams/$ref/archive/repo";
$etcaptPath = "/var/www/vhosts/getacos/streams/$ref/etc/apt";

if (!file_exists($repoBarePath)) {
        echo "Bare repository $repoBarePath don't exists";
        exit(1);
}
echo "repoBarePath=$repoBarePath\n";
$cmd = "ostree --repo=$repoBarePath log $ref";
$output = [];
exec($cmd, $output);

echo "LOG=" . print_r($output, 1);
$commits = [];
$commit = [];
foreach ($output as $line) {
  if (strlen(trim($line)) == 0 ) continue;
  $parts = explode(' ', $line);
  $name = $parts[0];
  if (trim($name) == 'commit') {
    if ($id = @$commit['id'] ) {
      $commits[] = $commit;
    }
    $id = trim($parts[1]);
    $commit['id'] = $id;
  } else {
    if (substr($name, -1) == ':') {
      $value = trim(implode(' ', array_slice($parts, 1)));
      $commit[$name] = $value;
    }

  }
}

if ($id = @$commit['id'] ) {
  $commits[] = $commit;
}

uasort($commits, 'cmpByVersion');
echo "COMMITS=" . print_r($commits, 1);
$lastCommit = $commits[0];
$lastCommitId = $lastCommit['id'];
$lastVersion = $lastCommit['Version:'];
if ($lastVersion != $version) {
  echo "Запрошенная версия $version не совпадает с последней версией $lastVersion\n";
  exit(1);
}

$rootsPath = "/var/www/vhosts/getacos/ACOS/streams/$ref/roots";
if (!is_dir($rootsPath)) {
  $cmd = "sudo mkdir $rootsPath";
  echo "MKDIRCMD=$cmd\n";
  system($cmd);
}

$commitPath = "$rootsPath/$lastCommitId";

if (!is_dir($commitPath)) {
  $cmd =  "cd $rootsPath; sudo ostree checkout --repo $repoBarePath $lastCommitId";
  $output = [];
  echo "CHECKOUTCMD=$cmd\n";
  exec($cmd, $output);
}

$cmd = "
cd $rootsPath; \
for dir in merged upper work/;\
do \
    if [ -d \$dir ];\
     then 
       sudo rm -rf \$dir; 
    fi; 
  sudo mkdir \$dir; 
done
cd $rootsPath
sudo mount -t overlay overlay -o lowerdir=$lastCommitId,upperdir=./upper,workdir=./work ./merged;
";
$output = [];
echo "OVERLAYCMD=$cmd\n";
exec($cmd, $output);
echo "OUTPUT=". print_r($output, 1);
#exit(1);

$cmd = "cd $rootsPath/merged;
sudo ln -sf  /usr/etc/ ./etc;
cd ..;
sudo apt-get update -o RPM::RootDir=$rootsPath/merged;
sudo apt-get dist-upgrade -y -o RPM::RootDir=$rootsPath/merged;
";

$output = [];
echo "UPDATECMD=$cmd\n";
exec($cmd, $output);
echo "UPDATE=" . print_r($output, 1);
#exit(0);
foreach ($output as $line) {
  if (strstr($line, 'upgraded') !== FALSE && strstr($line, 'installed') !== FALSE && strstr($line, 'removed') !== FALSE) {
    break;
  }
}

echo "RESULT=$line\n";
$changed = FALSE;
foreach (explode(' ', $line) as $value) {
  echo "VALUE=$value=" . is_int($value) . '=' .  intval($value) . "\n";	
  if (intval($value) > 0) {
    $changed = TRUE;
    echo "CHANGED\n";
    break;    
  }
}

if ($changed) {
  echo "CHANGED: $line\n";
} else {
  echo "NOT CHANGED\n";
}

$cmd = "
set -x;
cd $rootsPath/;
sudo rm -f ./upper/etc;
#ls -l ./merged;
echo 'UPPER=';ls -lR ./upper;
cd upper
echo 'DELETE==='
delete=`find . -type c`;
echo DELETE \$delete
sudo rm -rf \$delete
cd ../$lastCommitId/;
sudo rm -rf \$delete
cd ../upper;
#echo 'RM==='
#find . -type c -exec echo sudo rm -rf  $commitPath/{} 2>&1\;
echo 'CPIO===';
find . -depth | sudo cpio -plmvdu $commitPath/ 2>&1; 
cd ..
";

$output = [];
echo "SYNCCMD=$cmd\n";
exec($cmd, $output);
echo "SYNC=" . print_r($output, 1);
