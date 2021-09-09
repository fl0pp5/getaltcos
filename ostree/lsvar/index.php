<?php
$rootdir = $_SERVER['DOCUMENT_ROOT'];
$versionDir = implode('/', array_slice(explode('.', $_REQUEST['version']), 1));
$ref = $_REQUEST['ref'];
$path = "/ACOS/streams/${ref}/vars/$versionDir";
//echo "PATH=$path";
header("Location: $path");
