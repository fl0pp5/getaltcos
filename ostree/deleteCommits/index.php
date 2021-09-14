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

/*
ostree  --repo=/var/www/vhosts/getacos/ACOS/streams/acos/x86_64/sisyphus/bare/repo/ refs --delete acos/x86_64/sisyphus
ostree prune --repo /var/www/vhosts/getacos/ACOS/streams/acos/x86_64/sisyphus/bare/repo --delete-commit 1c7a3cfd1697637a93a312c25f685efa08e15189ec1de45c73f54808d26f144f
ostree  --repo=/var/www/vhosts/getacos/ACOS/streams/acos/x86_64/sisyphus/bare/repo/ refs --create=acos/x86_64/sisyphus a1e8e06542c6cc5e5f6b8b66677ecc6a0236ae7ee577f7c6fa2dfcb2a35b6251
*/

