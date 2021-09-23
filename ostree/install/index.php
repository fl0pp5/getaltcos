<?php
$rootdir = $_SERVER['DOCUMENT_ROOT'];
ini_set('include_path', "$rootdir/class");
require_once('repo.php');
require_once('refsConf.php');

//MAIN
$startTime = time();
$DOCUMENT_ROOT = $_SERVER['DOCUMENT_ROOT'];
putenv("DOCUMENT_ROOT=$DOCUMENT_ROOT");
$BINDIR = "$DOCUMENT_ROOT/ostree/bin";
$ref = $_REQUEST['ref'];
$subName = $_REQUEST['subName'];
$pkgs = $_REQUEST['pkgs'];
$refDir = repos::refRepoDir($ref);

$subRef = repos::subRef($ref, $subName);
$subVersion = repos::refVersion($subRef);
// $subVersionVarSubDir = repos::versionVarSubDir($subVersion);

$repoType = 'bare';
$repo = new repo($ref, $repoType);
// $refRepoDir = $repo->refRepoDir;

if (!$repo->haveConfig()) {
  echo "Bare repository $repoBarePath don't exists";
  exit(1);
}

$commits = $repo->getCommits($ref);
$lastCommitId = $repo->lastCommitId;
// $lastCommit = $repo->lastCommit;
// # echo "<pre>lastCommitId=$lastCommitId lastCommit=" . print_r($lastCommit, 1) . "</pre>";
// $lastVersion = $lastCommit['Version'];
// $versionVarSubDir = repos::versionVarSubDir($lastVersion);

$cmd = "$BINDIR/ostree_checkout.sh '$ref' '$lastCommitId' '$subRef'";
echo "CHECKOUTCMD=$cmd\n";
$output = [];
exec($cmd, $output);
echo "CHECKOUT=<pre>" . implode("\n",$output) . "</pre>";

$cmd = "$BINDIR/apt-get_update.sh $subRef";
echo "APT-GET_UPDATETCMD=$cmd\n";
$output = [];
exec($cmd, $output);
echo "APT-GET_UPDATE=<pre>" . implode("\n",$output). "</pre>";

$cmd = "$BINDIR/apt-get_install.sh $subRef $pkgs";
echo "APT-GET_INSTALL=$cmd\n";
$output = [];
exec($cmd, $output);
echo "APT-GET_INSTALL=<pre>" . implode("\n",$output). "</pre>";

$cmd = "$BINDIR/syncUpdates.sh $subRef $lastCommitId $subVersion";
echo "SYNCUPDATESCMD=$cmd\n";
$output = [];
exec($cmd, $output);
echo "SYNCUPDATES=<pre>" . implode("\n",$output). "</pre>";

$rpmList = $repo->rpmList($subVersion);
$refsConf = new refsConf($subRef, $subVersion, $pkgs);
$refsConf->addRpmList($rpmList);
$refsConf->save();

$cmd = "$BINDIR/ostree_commit.sh $subRef $lastCommitId $subVersion";
echo "COMMITCMD=$cmd\n";
$output = [];
exec($cmd, $output);
echo "COMMIT=<pre>" . implode("\n",$output). "</pre>";
