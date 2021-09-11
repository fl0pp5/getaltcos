<?php
$rootdir = $_SERVER['DOCUMENT_ROOT'];
ini_set('include_path', "$rootdir/class");
require_once('repo.php');


//MAIN
$startTime = time();
$DOCUMENT_ROOT = $_SERVER['DOCUMENT_ROOT'];
putenv("DOCUMENT_ROOT=$DOCUMENT_ROOT");
$BINDIR = "$DOCUMENT_ROOT/ostree/bin";
$ref = $_REQUEST['ref'];
$subName = $_REQUEST['subName'];
$pkgs = explode(',', $_REQUEST['pkgs']);
$commitId = $_REQUEST['commitId'];

$subRef = repo::subRef($ref, $subName);
$repoType = 'bare';
$repo = new repo($ref, $repoType);
$refDir = repos::refDir($ref);

if (!$repo->haveConfig()) {
  echo "Bare repository $repoBarePath don't exists";
  exit(1);
}

$commits = $repo->getCommits($ref);
$commitIds = array_keys($commits);
$lastCommitId = $commitIds[count($commitIds)-1];
$lastCommit = $commits[$lastCommitId];

if (!key_exists($commitId, $commits)) {
  echo "Запрошенный коммит $commitId отсутствует в репозитории";
  exit(1);
}

$commit = $commits[$commitId];
$version = $commit['Version'];


$cmd = "$BINDIR/ostree_checkout.sh '$refDir' '$commitId' '$version' 'all'";
echo "CHECKOUTCMD=$cmd\n";
$output = [];
exec($cmd, $output);
echo "CHECKOUT=<pre>" . print_r($output, 1) . "</pre>";

$cmd = "$BINDIR/apt-get_update.sh $ref";
echo "APT-GET_UPDATETCMD=$cmd\n";
$output = [];
exec($cmd, $output);
echo "APT-GET_UPDATE=<pre>" . print_r($output, 1). "</pre>";

$cmd = "$BINDIR/apt-get_install.sh $ref";
echo "APT-GET_INSTALL=$cmd\n";
$output = [];
exec($cmd, $output);
echo "APT-GET_INSTALL=<pre>" . print_r($output, 1). "</pre>";

$cmd = "$BINDIR/syncUpdates.sh $ref $nextVersion";
echo "SYNCUPDATESCMD=$cmd\n";
$output = [];
exec($cmd, $output);
echo "SYNCUPDATES=<pre>" . print_r($output, 1). "</pre>";

