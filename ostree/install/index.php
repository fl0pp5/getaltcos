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
$pkgs = $_REQUEST['pkgs'];

$subRef = repos::subRef($ref, $subName);
$version = repos::refVersion($subRef);
$versionVarSubDir = repos::versionVarSubDir($version);
$repoType = 'bare';
$repo = new repo($ref, $repoType);
$refRepoDir = $repo->refRepoDir;


if (!$repo->haveConfig()) {
  echo "Bare repository $repoBarePath don't exists";
  exit(1);
}

$commits = $repo->getCommits($ref);
$lastCommitId = $repo->lastCommitId;
$lastCommit = $repo->lastCommit;
# echo "<pre>lastCommitId=$lastCommitId lastCommit=" . print_r($lastCommit, 1) . "</pre>";
$lastVersion = $lastCommit['Version'];

$cmd = "$BINDIR/ostree_checkout.sh '$refRepoDir' '$lastCommitId' '$lastVersion' 'all'";
echo "CHECKOUTCMD=$cmd\n";
$output = [];
exec($cmd, $output);
echo "CHECKOUT=<pre>" . print_r($output, 1) . "</pre>";

$cmd = "$BINDIR/apt-get_update.sh $refRepoDir";
echo "APT-GET_UPDATETCMD=$cmd\n";
$output = [];
exec($cmd, $output);
echo "APT-GET_UPDATE=<pre>" . print_r($output, 1). "</pre>";

$cmd = "$BINDIR/apt-get_install.sh $refRepoDir $pkgs";
echo "APT-GET_INSTALL=$cmd\n";
$output = [];
exec($cmd, $output);
echo "APT-GET_INSTALL=<pre>" . print_r($output, 1). "</pre>";

$cmd = "$BINDIR/syncUpdates.sh $refRepoDir  $versionVarSubDir";
echo "SYNCUPDATESCMD=$cmd\n";
$output = [];
exec($cmd, $output);
echo "SYNCUPDATES=<pre>" . print_r($output, 1). "</pre>";

