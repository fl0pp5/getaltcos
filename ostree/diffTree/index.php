<?php
$rootdir = $_SERVER['DOCUMENT_ROOT'];
ini_set('include_path', "$rootdir/class");
require_once('repo.php');

$ref = $_REQUEST['ref'];
$commitId1 = $_REQUEST['commitId1'];
$commitId2 = $_REQUEST['commitId2'];
// $path1 = "/ACOS/streams/${ref}/roots/$commitId1";
// $dir1 = "$rootdir/$path1";
// $path2 = "/ACOS/streams/${ref}/roots/$commitId2";
// $dir2 = "$rootdir/$path2";
$rootsDir = "$rootdir/ACOS/streams/${ref}/roots/";
$repo = new repo($ref, 'bare');
$repo->checkout($commitId1, true);
$repo->checkout($commitId2, true);

$cmd = "cd $rootsDir; sudo diff -r $commitId1 $commitId2 2>&1";
// echo "CMD = $cmd<br>\n";
$output = [];
exec($cmd, $output);
echo implode("<br>\n", $output);


