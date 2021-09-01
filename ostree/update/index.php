<?php
$rootdir = $_SERVER['DOCUMENT_ROOT'];
ini_set('include_path', "$rootdir/class");
require_once('repo.php');

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
$DOCUMENT_ROOT = $_SERVER['DOCUMENT_ROOT'];
putenv("DOCUMENT_ROOT=$DOCUMENT_ROOT");
$BINDIR = "$DOCUMENT_ROOT/ostree/bin";
$ref = $_REQUEST['ref'];
$commitId = $_REQUEST['commitId'];

$repoType = 'bare';
$repo = new repo($ref, $repoType);

if (!$repo->haveConfig()) {
        echo "Bare repository $repoBarePath don't exists";
        exit(1);
}

$commits = $repo->getCommits($ref);

$commitIds = array_keys($commits);

$lastCommitId = $commitIds[count($commitIds)-1];

$lastCommit = $commits[$lastCommitId];
$lastVersion = $lastCommit['Version:'];

if ($lastCommitId != $commitId) {
  echo "Запрошенная коммит версии $version не совпадает с последнем коммитом $commitId\n";
  exit(1);
}

list($stream, $date, $major, $minor) = explode('.', $lastVersion);
$nextMinor = $minor + 1;
$nextVersion = "$stream.$date.$major$nextMinor";

$cmd = "$BINDIR/ostree_checkout.sh $ref $lastCommitId all";
echo "CHECKOUTCMD=$cmd\n";
$output = [];
exec($cmd, $output);
echo "CHECKOUT=" . print_r($output, 1);
//exit(0);

$cmd = "$BINDIR/apt-get_update.sh $ref";
echo "APT-GET_UPDATETCMD=$cmd\n";
$output = [];
exec($cmd, $output);
echo "APT-GET_UPDATE=" . print_r($output, 1);
//exit(0);

$cmd = "$BINDIR/apt-get_dist-upgrade.sh $ref";
echo "APT-GET_DIST-UPGRADECMD=$cmd\n";
$output = [];
exec($cmd, $output);
echo "APT-GET_DIST-UPGRADE=" . print_r($output, 1);

if (!isUpdated($output)) {
  echo "Обновлений нет";
  exit(0);
}

$cmd = "$BINDIR/syncUpdates.sh $ref";
echo "SYNCUPDATESCMD=$cmd\n";
$output = [];
exec($cmd, $output);
echo "SYNCUPDATES=" . print_r($output, 1);

$cmd = "$BINDIR/ostree_commit.sh $ref $lastCommitId $nextVersion";
echo "COMMITCMD=$cmd\n";
$output = [];
exec($cmd, $output);
echo "COMMIT=" . print_r($output, 1);

$cmd = "$BINDIR/ostree_pull-local.sh";
echo "PULLCMD=$cmd\n";
$output = [];
exec($cmd, $output);
echo "PULL=" . print_r($output, 1);

