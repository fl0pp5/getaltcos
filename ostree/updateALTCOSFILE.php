<?php
// phpinfo(); //exit(0);
$rootdir = $_SERVER['DOCUMENT_ROOT'];
ini_set('include_path', "$rootdir/class");
require_once('altcosfile.php');

$error = $_FILES['ALTCOSfile']['error'];
if ($error != UPLOAD_ERR_OK) {
  echo "Неуспешная загрузка файла. Error= $error";
  exit(1);
}

$file = $_FILES['ALTCOSfile']['tmp_name'];
if (!$file || strlen($file) == 0) {
  echo "Файл не выбран";
  exit(1);
}
// echo "FILE=$file LEN=" . strlen($file);
list($data, $error) = altcosfile::loadALTCOSfile($file);
if ($error) {
  echo $error;
  exit(1);
}
// echo "<pre>DATA=".print_r($data, 1). "</pre>";
if (!key_exists('version', $data)) {
  echo "Поле version отсутсвует";
  exit(1);
}

$ref = $_REQUEST['ref'];
$toFile = $_SERVER['DOCUMENT_ROOT'] . altcosfile::getFilePath($ref);
// echo "<pre>$file -> $toFile</pre>";
move_uploaded_file($file, $toFile);
echo "<b>ALTCOS-файл ветки  $ref обновлен</b>";
echo "<pre>".print_r($data, 1). "</pre>";

