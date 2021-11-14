<?php
$rootdir = $_SERVER['DOCUMENT_ROOT'];
ini_set('include_path', "$rootdir/class");
require_once "altcosfile.php";

$DOCUMENT_ROOT = $_SERVER['DOCUMENT_ROOT'];
putenv("DOCUMENT_ROOT=$DOCUMENT_ROOT");
$BINDIR = "$DOCUMENT_ROOT/ostree/bin";
$subRef = $_REQUEST['ref'];

$subRef = $_REQUEST['altcosfileref'];
$subVersion = repos::refVersion($subRef);
$subVersionVarSubDir = repos::versionVarSubDir($subVersion);

$altcosfile = new altcosfile($subRef);
echo "<pre>". json_encode($altcosfile, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) . "</pre>";
$ref = $altcosfile->ref;

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
$versionVarSubDir = repos::versionVarSubDir($lastVersion);

$cmd = "$BINDIR/ostree_checkout.sh '$refRepoDir' '$lastCommitId' '$versionVarSubDir' 'all'";
echo "CHECKOUTCMD=$cmd\n";
$output = [];
exec($cmd, $output);
echo "CHECKOUT=<pre>" . print_r($output, 1) . "</pre>";

