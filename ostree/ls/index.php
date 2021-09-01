<?php
//phpinfo();
$rootdir = $_SERVER['DOCUMENT_ROOT'];
ini_set('include_path', "$rootdir/class");
require_once('repo.php');
$ref = $_REQUEST['ref'];
$repoType = $_REQUEST['repoType'];
$commitId = $_REQUEST['commitId'];

$repo = new repo($ref, $repoType);

?>
<!DOCTYPE html>
<html>
<head>
<title>Содержимое файловой системы репозитория</title> 
</head>
<body>
<?php 
$result = $repo->ls($commitId);
echo "<pre>" . implode("\n", $result) . "</pre>";
