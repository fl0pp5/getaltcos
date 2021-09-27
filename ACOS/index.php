<?php
$rootdir = $_SERVER['DOCUMENT_ROOT'];
ini_set('include_path', "$rootdir/class");
require_once('repo.php');
require_once('repos.php');
?>
<html lang='ru'>
<head>
<title>Интерфейс пользователя ACOS</title>
</head>
<body>

<h1>Интерфейс пользователя ACOS</h2>
<ul>
  <li><a href='/pdf/usermanual.pdf'><button type='button'>Инструкция пользователя ACOS</button></a></li>
</ul>

<h2>Поддерживаемые потоки</h3>
<?php
foreach (repos::listOSs() as $os) {
?>
<h3>Операционная система <?= repos::getOSName($os)?> (<?= $os?>)</h3>
<?php
  foreach (repos::listArchs() as $arch) {
?>
<h4>Архитектура <?= "$os/$arch"?></h3>
<?php
    $streams = repos::listStreams($os, $arch);
    // echo "<pre>STREAMS=".print_r($streams, 1) . "</pre>\n";
    foreach ($streams as $stream) {
      $ref = "$os/$arch/$stream";
      $repo = new repo($ref, 'bare');
      $repo->getCommits();
      $commits = array_reverse($repo->commits, true);
    ?>
<h5>Поток <?= "$ref"?></h3>
<a href='changelog.php?ref=<?= $ref?>'  target='clientsWindow'><button type='button'>История изменений</button></a>
<table border='1'>
  <tr>
    <th rowspan='3'>Дата</th>
    <th rowspan='3'>Версия</th>
<?php
      $imageTypes = $repo->getImagesTypes();
      $ncols = count($imageTypes) * 2;
?>
    <th colspan=<?= $ncols?>>Образы</th>
  </tr>
  <tr>
<?php
      foreach ($imageTypes as $imageType) {
?>
    <th colspan='2'><?= $imageType?></th>
<?php
      }
?>
  </tr>
  <tr>
<?php
      foreach ($imageTypes as $imageType) {
?>
    <th>Полный</th>
    <th>Сжатый</th>
<?php
      }
?>
  </tr>
<?php
      foreach ($commits as $commitId => $commit) {
        $Date = $commit['Date'];
        $version = $commit['Version'];
?>
  <tr>
    <td><?= $Date?></td>
    <td><?= $version?></td>
    <td>
<?php
      $fullImage = $repo->getFullImageName($imageType, $version);
      $fullImageSize = $repo->getFullImageSize($imageType, $version);
      if ($fullImage) {
        $ref = "/ACOS/streams/$os/$arch/$stream/images/$imageType/$fullImage";
?>
      <a href='<?= $ref?>' title='<?= $fullImage?>'><button type='button'>Скачать(<?= $fullImageSize?>)</button></a>
<?php
      } else {
?>-<?php
      }
?>
    </td>
    <td>
<?php
      $compressedImage = $repo->getCompressedImageName($imageType, $version);
      $compressedImageSize = $repo->getCompressedImageSize($imageType, $version);
      if ($compressedImage) {
        $ref = "/ACOS/streams/$os/$arch/$stream/images/$imageType/$compressedImage";
?>
      <a href='<?= $ref?>' title='<?= $compressedImage?>'><button type='button'>Скачать(<?= $compressedImageSize?>)</button></a>
<?php
      } else {
?>-<?php
      }
?>
    </td>
  </tr>
<?php
      }
?>
</table>
<?php
    }
  }
}
