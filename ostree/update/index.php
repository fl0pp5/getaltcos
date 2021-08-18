<?php
function cmpByVersion($commit1, $commit2) {
  $ret = strcmp($commit2['Version:'], $commit1['Version:']);
  return $ret;
}

function getLastCommit($output) {
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
  return $lastCommit;
}


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
$version = $_REQUEST['version'];

$repoBarePath = "$DOCUMENT_ROOT/ACOS/streams/$ref/bare/repo";
$repoArchivePath = "$DOCUMENT_ROOT/ACOS/streams/$ref/archive/repo";

if (!file_exists($repoBarePath)) {
        echo "Bare repository $repoBarePath don't exists";
        exit(1);
}
echo "repoBarePath=$repoBarePath\n";

$cmd = "$BINDIR/ostree_log.sh $ref";
echo "LOGCMD=$cmd\n";
$output = [];
exec($cmd, $output);
echo "LOG=" . print_r($output, 1);
# exit(0);

$lastCommit = getLastCommit($output);
$lastCommitId = $lastCommit['id'];
$lastVersion = $lastCommit['Version:'];

if ($lastVersion != $version) {
  echo "Запрошенная версия $version не совпадает с последней версией $lastVersion\n";
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
exit(0);

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

