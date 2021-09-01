<?php
//phpinfo();
$rootdir = $_SERVER['DOCUMENT_ROOT'];
ini_set('include_path', "$rootdir/class");
require_once('repo.php');
$ref = $_REQUEST['ref'];
$repoType = $_REQUEST['repoType'];
$commitId = $_REQUEST['commitId'];

$repo = new repo($ref, $repoType);

/*
ostree  --repo=/var/www/vhosts/getacos/ACOS/streams/acos/x86_64/sisyphus/bare/repo/ refs --delete acos/x86_64/sisyphus
ostree prune --repo /var/www/vhosts/getacos/ACOS/streams/acos/x86_64/sisyphus/bare/repo --delete-commit 1c7a3cfd1697637a93a312c25f685efa08e15189ec1de45c73f54808d26f144f
ostree  --repo=/var/www/vhosts/getacos/ACOS/streams/acos/x86_64/sisyphus/bare/repo/ refs --create=acos/x86_64/sisyphus a1e8e06542c6cc5e5f6b8b66677ecc6a0236ae7ee577f7c6fa2dfcb2a35b6251
*/

