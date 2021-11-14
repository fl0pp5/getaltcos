<?php
$rootdir = $_SERVER['DOCUMENT_ROOT'];
ini_set('include_path', "$rootdir/class");
require_once "altcosfile.php";

$DOCUMENT_ROOT = $_SERVER['DOCUMENT_ROOT'];
putenv("DOCUMENT_ROOT=$DOCUMENT_ROOT");
$BINDIR = "$DOCUMENT_ROOT/ostree/bin";
$ref = $_REQUEST['ref'];

$subVersion = repos::refVersion($ref);
$subVersionVarSubDir = repos::versionVarSubDir($subVersion);

$altcosfile = new altcosfile($ref);
echo "<pre>". json_encode($altcosfile, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) . "</pre>";
$parentRef = repos::parentRef($ref);
echo "<pre>PARENTREF=" . print_r($parentRef, 1) . "</pre>";

$parentRepo = new repo($parentRef, 'bare');
$refRepoDir = $parentRepo->refRepoDir;
if (!$parentRepo->haveConfig()) {
  echo "Bare repository $parentRepoBarePath don't exists";
  exit(1);
}

$commits = $parentRepo->getCommits($parentRef);
echo "<pre>REPO=" . print_r($parentRepo, 1) . "</pre>";
$lastCommitId = $parentRepo->lastCommitId;
$lastCommit = $parentRepo->lastCommit;
echo "<pre>lastCommitId=$lastCommitId lastCommit=" . print_r($lastCommit, 1) . "</pre>";
$lastVersion = $lastCommit['Version'];
$versionVarSubDir = repos::versionVarSubDir($lastVersion);

$cmd = "$BINDIR/ostree_checkout.sh '$parentRef' '$lastCommitId' '$ref' 'all'";
echo "CHECKOUTCMD=$cmd\n";
$output = [];
exec($cmd, $output);
echo "CHECKOUT=<pre>" . print_r($output, 1) . "</pre>";

$cmd = "$BINDIR/apt-get_update.sh $ref";
echo "APT-GET_UPDATETCMD=$cmd\n";
$output = [];
exec($cmd, $output);
echo "APT-GET_UPDATE=<pre>" . implode("\n",$output). "</pre>";


$rpms = implode(' ', $altcosfile->getRPMS());
$cmd = "$BINDIR/apt-get_install.sh '$ref' $rpms";
echo "APT-GET_INSTALL=$cmd\n";
$output = [];
exec($cmd, $output);
echo "APT-GET_INSTALL=<pre>" . implode("\n",$output). "</pre>";


