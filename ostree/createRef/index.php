<?php
$rootdir = $_SERVER['DOCUMENT_ROOT'];
ini_set('include_path', "$rootdir/class");
require_once('repo.php');
require_once('refsConf.php');

$DOCUMENT_ROOT = $_SERVER['DOCUMENT_ROOT'];
putenv("DOCUMENT_ROOT=$DOCUMENT_ROOT");
$BINDIR = "$DOCUMENT_ROOT/ostree/bin";
$ref = $_REQUEST['ref'];

if (repos::isBaseRef($ref)) {
  $cmd = "sudo $BINDIR/rootfs_to_repo.sh $ref";
  echo "ROOTFS_TO_REPO=$cmd\n";
  $output = [];
  exec($cmd, $output);
  echo "ROOTFS_TO_REPO=<pre>" . print_r($output, 1) . "</pre>";
  $repo = new repo($ref);
  $repo->getCommits();
  echo "<pre>REPO=" . print_r($repo, 1) . "</pre>\n";
  $commitId = $repo->lastCommitId;
  $version = $repo->getCommitVersion($commitId);
  $rpmList = $repo->rpmList($version);
  $refsConf = new refsConf($ref, $version);
  $refsConf->addRpmList($rpmList);
  $refsConf->save();
}
