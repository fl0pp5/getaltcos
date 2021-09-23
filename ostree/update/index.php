<?php
$rootdir = $_SERVER['DOCUMENT_ROOT'];
ini_set('include_path', "$rootdir/class");
require_once('repo.php');
require_once('refsConf.php');

function isUpdated($output) {
  foreach ($output as $line) {
    if (strstr($line, 'upgraded') !== FALSE && strstr($line, 'installed') !== FALSE && strstr($line, 'removed') !== FALSE) {
      break;
    }
  }
//   echo "RESULT=$line\n";
  $changed = FALSE;
  foreach (explode(' ', $line) as $value) {
//     echo "VALUE=$value=" . is_int($value) . '=' .  intval($value) . "\n";
    if (intval($value) > 0) {
      $changed = TRUE;
//       echo "CHANGED\n";
      break;
    }
  }
  return $changed;
}
//phpinfo();//exit(0);

//MAIN
$startTime = time();
$DOCUMENT_ROOT = $_SERVER['DOCUMENT_ROOT'];
putenv("DOCUMENT_ROOT=$DOCUMENT_ROOT");
$BINDIR = "$DOCUMENT_ROOT/ostree/bin";
$ref = $_REQUEST['ref'];
$commitId = $_REQUEST['commitId'];
$refRepoDir = repos::refRepoDir($ref);
$version = repos::refVersion($ref, $commitId);
// $versionVarSubDir = repos::versionVarSubDir($version);

$repoType = 'bare';
$repo = new repo($ref, $repoType);

if (!$repo->haveConfig()) {
        echo "Bare repository $repoBarePath don't exists";
        exit(1);
}

$commits = $repo->getCommits();
$lastCommitId = $repo->lastCommitId;
$lastCommit = $repo->lastCommit;
# echo "<pre>lastCommitId=$lastCommitId lastCommit=" . print_r($lastCommit, 1) . "</pre>";
$lastVersion = $lastCommit['Version'];

if ($lastCommitId != $commitId) {
  echo "Запрошенная коммит версии $version не совпадает с последнем коммитом $commitId\n";
  exit(1);
}

$versionVarSubDir = repos::versionVarSubDir($version);
list($stream, $date, $major, $minor) = explode('.', $lastVersion);
$nextMinor = intval($minor) + 1;
$nextVersion = "$stream.$date.$major.$nextMinor";
$nextVersionVarSubDir = repos::versionVarSubDir($nextVersion);


$cmd = "$BINDIR/ostree_checkout.sh '$ref' '$lastCommitId'";
echo "CHECKOUTCMD=$cmd\n";
$output = [];
exec($cmd, $output);
echo "CHECKOUT=<pre>" . print_r($output, 1) . "</pre>";
//exit(0);

$cmd = "$BINDIR/apt-get_update.sh $ref";
echo "APT-GET_UPDATETCMD=$cmd\n";
$output = [];
exec($cmd, $output);
echo "APT-GET_UPDATE=<pre>" . print_r($output, 1). "</pre>";
//exit(0);

$rpmListFile = tempnam('/tmp', 'ostree_');
echo "<br>rpmListFile=$rpmListFile<br>";
$cmd = "$BINDIR/apt-get_dist-upgrade.sh $ref '$rpmListFile'";
echo "APT-GET_DIST-UPGRADECMD=$cmd\n";
$output = [];
exec($cmd, $output);
echo "APT-GET_DIST-UPGRADE=<pre>" . print_r($output, 1). "</pre>";
// exit(0);
$fp = fopen($rpmListFile, 'r');
$rpmList = explode("\n", fread($fp, filesize($rpmListFile)));
fclose($fp);
//unlink($rpmListFile);

if (!isUpdated($output)) {
  echo "Обновлений нет";
  $endTime = time();
  echo "Время выполнения скрипта " . ($endTime - $startTime) . " секунд\n";
  exit(0);
}

$RpmList = [];
foreach ($rpmList as $rpm) {
  if (strlen(trim($rpm)) == 0) continue;
  $RpmList[] = $rpm;
}
$refsConf = new refsConf($ref, $lastVersion);
$refsConf->addRpmList($RpmList);
$refsConf->save();

$cmd = "$BINDIR/syncUpdates.sh $ref $lastCommitId $nextVersion";
echo "SYNCUPDATESCMD=$cmd\n";
$output = [];
exec($cmd, $output);
echo "SYNCUPDATES=<pre>" . print_r($output, 1). "</pre>";

$cmd = "$BINDIR/ostree_commit.sh $ref $lastCommitId $nextVersion";
echo "COMMITCMD=$cmd\n";
$output = [];
exec($cmd, $output);
echo "COMMIT=<pre>" . print_r($output, 1). "</pre>";
$commitId = pop($output);

// $cmd = "$BINDIR/ostree_pull-local.sh $refRepoDir";
// echo "PULLCMD=$cmd\n";
// $output = [];
// exec($cmd, $output);
// echo "PULL=<pre>" . print_r($output, 1). "</pre>";
// $endTime = time();
// echo "Время выполнения скрипта " . ($endTime - $startTime) . " секунд\n";

