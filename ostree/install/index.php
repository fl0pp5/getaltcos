<?php
$rootdir = $_SERVER['DOCUMENT_ROOT'];
ini_set('include_path', "$rootdir/class");
require_once('repo.php');


//MAIN
$startTime = time();
$DOCUMENT_ROOT = $_SERVER['DOCUMENT_ROOT'];
putenv("DOCUMENT_ROOT=$DOCUMENT_ROOT");
$BINDIR = "$DOCUMENT_ROOT/ostree/bin";
$ref = $_REQUEST['ref'];
$subName = $_REQUEST['subName'];
$pkgs = explode(',', $_REQUEST['pkgs']);

$subRef = repo::subRef($ref, $subName);
$repoType = 'bare';
$repo = new repo($ref, $repoType);

if (!$repo->haveConfig()) {
  echo "Bare repository $repoBarePath don't exists";
  exit(1);
}

