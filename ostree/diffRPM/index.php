<?php
$rootdir = $_SERVER['DOCUMENT_ROOT'];
$version1Dir = implode('/', array_slice(explode('.', $_REQUEST['version1']), 1));
$version2Dir = implode('/', array_slice(explode('.', $_REQUEST['version2']), 1));
$ref = $_REQUEST['ref'];
$varsDir = "$rootdir/ACOS/streams/${ref}/vars/";
$path1 = "$varsDir/$version1Dir";
$path2 = "$varsDir/$version2Dir";
// echo "PATH1=$path1";
// echo "PATH2=$path2";

$tmpDir = "$rootdir/ACOS/tmp/" . $_SERVER['REQUEST_TIME_FLOAT'];

mkdir($tmpDir, 0777, true);

$cmd = "rpm -qa -r $path1 | sort > $tmpDir/a";
$output = [];
// echo "<pre>CMD=$cmd</pre>\n";
exec($cmd, $output);
// echo "<pre>RPMa=" . print_r($output, 1) . "</pre>\n";

$cmd = "rpm -qa -r $path2 | sort > $tmpDir/b";
$output = [];
// echo "<pre>CMD=$cmd</pre>\n";
exec($cmd, $output);
// echo "<pre>RPMb=" . print_r($output, 1) . "</pre>\n";

$cmd = "cd $tmpDir; diff b a";
$output = [];
// echo "<pre>CMD=$cmd</pre>\n";
exec($cmd, $output);
// echo "<pre>DIFF=" . print_r($output, 1) . "</pre>\n";
echo implode("<br>\n", $output);

unlink("$tmpDir/a");
unlink("$tmpDir/b");
rmdir($tmpDir);
