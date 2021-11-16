<?php
$rootdir = $_SERVER['DOCUMENT_ROOT'];
ini_set('include_path', "$rootdir/class");
require_once "repos.php";
$versionDir = repos::versionVarSubDir($_REQUEST['version']);
// implode('/', array_slice(explode('.', $_REQUEST['version']), 1));
$ref = $_REQUEST['ref'];
$refDir = repos::refToDir($ref);
$path = "/ALTCOS/streams/$refDir/vars/$versionDir";
//echo "PATH=$path";
header("Location: $path");
