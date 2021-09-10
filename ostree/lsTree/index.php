<?php
$rootdir = $_SERVER['DOCUMENT_ROOT'];
ini_set('include_path', "$rootdir/class");
require_once('repo.php');

$ref = $_REQUEST['ref'];
$commitId = $_REQUEST['commitId'];
$refDir = repos::refDir($ref);
$path = "/ACOS/streams/$refDir/roots/$commitId";
$dir = "$rootdir/$path";
//echo "PATH=$path";
$repo = new repo($ref, 'bare');
$repo->checkout($commitId, true);


//echo $path;
header("Location: $path");
