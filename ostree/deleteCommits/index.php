<?php
//phpinfo();
$rootdir = $_SERVER['DOCUMENT_ROOT'];
ini_set('include_path', "$rootdir/class");
require_once('repo.php');
$ref = $_REQUEST['ref'];
$repoType = $_REQUEST['repoType'];
$ids = $_REQUEST['ids'];

$repo = new repo($ref, $repoType);
$commits = $repo->getCommits();
echo "<pre>COMMITS=" . print_r($commits, 1) . "</pre>\n";
//Оставить неудаляемые
foreach ($ids as $id) {
  $commit = $commits[$id];
  $version = $commit['Version'];
  echo "<pre>Version=$version Commit=" . print_r($commit, 1) . "</pre>";
  $repo->deleteVarDir($version);
  unset($commits[$id]);
}

$repo->deleteRef();
foreach ($ids as $id) {
  $repo->deleteCommit($id);
}

$revCommitIds = array_reverse(array_keys($commits));
$repo->createRef($revCommitIds[0]);


