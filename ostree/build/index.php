<?php
$rootdir = $_SERVER['DOCUMENT_ROOT'];
ini_set('include_path', "$rootdir/class");
require_once "acosfile.php";

$ref = $_REQUEST['acosfileref'];
$acosfile = new acosfile($ref);
echo "<pre>". json_encode($acosfile, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) . "</pre>";
