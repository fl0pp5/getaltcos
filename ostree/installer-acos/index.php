<?php
$rootdir = $_SERVER['DOCUMENT_ROOT'];
ini_set('include_path', "$rootdir/class");
require_once "repos.php";
$ref = $_REQUEST['ref'];
$refDir = repos::refRepoDir($ref);
$versionDir = repos::versionVarSubDir($_REQUEST['version']);
$commitId = $_REQUEST['commitId'];

echo "<pre>RUN:
export DOCUMENT_ROOT=$rootdir
$rootdir/ostree/bin/installer-acos.sh $refDir $ref $commitId $versionDir
</pre>";
