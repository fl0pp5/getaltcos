<?php
$rootdir = $_SERVER['DOCUMENT_ROOT'];
ini_set('include_path', "$rootdir/class");
require_once('repo.php');
require_once('repos.php');

$ref = $_REQUEST['ref'];
$version = $_REQUEST['version'];
$versionDir = repos::versionVarSubDir($version);

$refDir = repos::refToDir($ref);
$varsDir = "$rootdir/ACOS/streams/${refDir}/vars";
$path = "$varsDir/$versionDir";

$cmd = "rpm -qa -r $path | sort ";
$output = [];
echo "<pre>CMD=$cmd</pre>\n";
exec($cmd, $output);
// echo "<pre>RPMa=" . print_r($output, 1) . "</pre>\n";
echo "<pre>" . implode("\n", $output) . "</pre>";
