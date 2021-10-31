<?php
//phpinfo();
$rootdir = $_SERVER['DOCUMENT_ROOT'];
ini_set('include_path', "$rootdir/class");
require_once('repo.php');
require_once('repos.php');

$streams = repos::listStreams();
echo json_encode($streams, JSON_PRETTY_PRINT);
