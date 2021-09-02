<?php
//phpinfo();
$rootdir = $_SERVER['DOCUMENT_ROOT'];
ini_set('include_path', "$rootdir/class");
require_once('repo.php');
$ref = $_REQUEST['ref'];
$repoType = 'bare';
$repo = new repo($ref, 'bare');
$archiveRepo = new repo($ref, 'archive');

if (!$repo->haveConfig()) {
        echo "Bare repository $repoBarePath don't exists";
        exit(1);
}

if (!$archiveRepo->haveConfig()) {
  $archiveRepo->init();
}

$commits = $repo->getCommits($ref);

$commitIds = array_keys($commits);

foreach ($commitIds as $commitId) {
    $cmd = "sudo ostree pull-local " . $repo->repoDir . " $ref $commitId --repo=".$archiveRepo->repoDir .' 2>&1';;
    $output = [];
    echo "<pre>CMD=$cmd</pre>\n";
    exec($cmd, $output);
    echo "<pre>REFS=" . print_r($output, 1) . "</pre>\n";  
}

