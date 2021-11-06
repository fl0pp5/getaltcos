<?php
//phpinfo();
$rootdir = $_SERVER['DOCUMENT_ROOT'];
ini_set('include_path', "$rootdir/class");
require_once('repo.php');
$ref = $_REQUEST['ref'];
$repoType = $_REQUEST['repoType'];
$commitId1 = $_REQUEST['commitId1'];
$commitId2 = $_REQUEST['commitId2'];

$repo = new repo($ref, $repoType);

?>
<!DOCTYPE html>
<html>
<head>
<title>Разность ostree-версий репозитория</title>
</head>
<body>
<?php
$result = $repo->diff($commitId2, $commitId1);
echo "<pre>" . implode("\n", $result) . "</pre>";
