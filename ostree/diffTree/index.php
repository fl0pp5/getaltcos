<?php
$rootdir = $_SERVER['DOCUMENT_ROOT'];
ini_set('include_path', "$rootdir/class");
require_once('repo.php');

$ref = $_REQUEST['ref'];
$commitId1 = $_REQUEST['commitId1'];
$commitId2 = $_REQUEST['commitId2'];
$rootsDir = "$rootdir/ALTCOS/streams/${ref}/roots/";
$repo = new repo($ref, 'bare');
$repo->checkout($commitId1, true);
$repo->checkout($commitId2, true);

$cmd = "cd $rootsDir; sudo diff -r $commitId1 $commitId2 | grep -v 'No such file or directory' 2>&1";
// echo "CMD = $cmd<br>\n";
$output = [];
exec($cmd, $output);
echo implode("<br>\n", $output);


