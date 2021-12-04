<?php
// phpinfo(); exit(0);
$rootdir = $_SERVER['DOCUMENT_ROOT'];
ini_set('include_path', "$rootdir/class");
require_once('log.php');
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
$log = new log('update');
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
  $log->write("Error: Bare repository $repoBarePath don't exists\n");
  exit(1);
}

$commits = $repo->getCommits();
$lastCommitId = $repo->lastCommitId;
$lastCommit = $repo->lastCommit;
# echo "\nlastCommitId=$lastCommitId lastCommit=" . print_r($lastCommit, 1) . "\n";
$lastVersion = $lastCommit['Version'];

if ($lastCommitId != $commitId) {
  $log->write("Error: Запрошенная коммит версии $version не совпадает с последнем коммитом $commitId\n");
  exit(1);
}

$versionVarSubDir = repos::versionVarSubDir($version);
list($stream, $date, $major, $minor) = explode('.', $lastVersion);
if (key_exists('date', $_REQUEST) && key_exists('major', $_REQUEST) && key_exists('minor', $_REQUEST)) {
  $nextVersion = $_REQUEST['date'] . '.' . intVal($_REQUEST['major']) . '.' . intVal($_REQUEST['minor']);
} else {
  $nextMinor = intval($minor) + 1;
  $nextVersion = "$stream.$date.$major.$nextMinor";
}
$nextVersionVarSubDir = repos::versionVarSubDir($nextVersion);

$cmd = "$BINDIR/clear_roots.sh '$ref'";
$log->write("CLEAR_ROOTS_CMD=$cmd\n");
$output = [];
exec($cmd, $output);
$log->write("CLEAR_ROOTS_OUT:" . implode("\n", $output) . "\n");

$cmd = "$BINDIR/ostree_checkout.sh '$ref' '$lastCommitId'";
$log->write("CHECKOUTCMD=$cmd\n");
$output = [];
exec($cmd, $output);
$log->write("CHECKOUT:" . implode("\n", $output) . "\n");
//exit(0);

$cmd = "$BINDIR/apt-get_update.sh $ref";
$log->write("APT-GET_UPDATETCMD=$cmd\n");
$output = [];
exec($cmd, $output);
$log->write("APT-GET_UPDATE:" . implode("\n", $output). "\n");
//exit(0);

$rpmListFile = tempnam('/tmp', 'ostree_');
$log->write("rpmListFile=$rpmListFile<\n");
$cmd = "$BINDIR/apt-get_dist-upgrade.sh $ref '$rpmListFile'";
$log->write("APT-GET_DIST-UPGRADECMD=$cmd\n");
$output = [];
exec($cmd, $output);
$log->write("APT-GET_DIST-UPGRADE=\n" . implode("\n", $output). "\n");
// exit(0);
$fp = fopen($rpmListFile, 'r');
$rpmList = explode("\n", fread($fp, filesize($rpmListFile)));
fclose($fp);
unlink($rpmListFile);

if (!isUpdated($output)) {
  $log->write("Обновлений нет");
  $endTime = time();
  $log->write("Время выполнения скрипта " . ($endTime - $startTime) . " секунд\n");
  echo json_encode(['new'=>[], 'changed'=>[], 'deleted'=>[]], JSON_PRETTY_PRINT);
  exit(1);
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
$log->write("SYNCUPDATESCMD=$cmd\n");
$output = [];
exec($cmd, $output);
$log->write("SYNCUPDATES=\n" . implode("\n", $output). "\n");

$cmd = "$BINDIR/ostree_commit.sh $ref $lastCommitId $nextVersion";
$log->write("COMMITCMD=$cmd\n");
$output = [];
exec($cmd, $output);
$log->write("COMMIT=\n" . implode("\n", $output). "\n");
$commitId = array_pop($output);

// echo "<pre>VERSION=$version NEXTVERSION=$nextVersion COMMITID=$commitId</pre>\n";
$ret = $repo->cmpRPMs($commitId, $version);
echo json_encode($ret, JSON_PRETTY_PRINT);
exit(0);


