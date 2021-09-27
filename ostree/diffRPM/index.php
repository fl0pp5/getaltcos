<?php
$rootdir = $_SERVER['DOCUMENT_ROOT'];
ini_set('include_path', "$rootdir/class");
require_once('repo.php');

// $version1Dir = implode('/', array_slice(explode('.', $_REQUEST['version1']), 1));
// $version2Dir = implode('/', array_slice(explode('.', $_REQUEST['version2']), 1));
$ref = $_REQUEST['ref'];
$version1 = $_REQUEST['version1'];
$version2 = $_REQUEST['version2'];

$repo = new repo($ref, 'bare');
$rpmDiff = $repo->cmpRPMs($version1, $version2);
// echo "<pre>RPMDIFF=" . print_r($rpmDiff, 1) . "</pre>";
?>
<h2>Сравнение пакетов версии <?= $version1?> и  <?= $version2?></h2>
<ul>
<li>
  <h3>Новые</h3>
  <ul>
<?php
  $new = $rpmDiff['new'];
  if (count($new) == 0) {
?>
<b><i><u>Отсутствуют</u></i></b>
<?php
  } else {
    $rpmsInfo = $repo->rpmsInfo(array_keys($new), $version1, ['Summary']);
    foreach ($new as $short=>$full) {
?>
    <li><b><?= $full?></b> - <i><?= $rpmsInfo[$short]['Summary']?></i></li>
<?php
    }
  }
?>
  </ul>
</li>
<li>
  <h3>Обновленные</h3>
  <ul>
<?php
  $changed = $rpmDiff['changed'];

  if (count($changed) == 0) {
?>
<b><i><u>Отсутствуют</u></i></b>
<?php
  } else {
    $rpmsInfo = $repo->rpmsInfo(array_keys($changed), $version1, ['Summary']);
  //   echo "<pre>RPMSINFO=" . print_r($rpmsInfo, 1) . "</pre>";
    foreach ($changed as $short=>$list) {
      $full1 = $list[0];
      $full2 = $list[1];
?>
    <li><b><?= $full1?></b> =&gt; <b><?= $full2?></b> - <i><?= $rpmsInfo[$short]['Summary']?></i></li>
<?php
    }
  }
?>
  </ul>
</li>
<li>
  <h3>Удаленные</h3>
  <ul>
<?php
  $deleted = $rpmDiff['deleted'];
  if (count($deleted) == 0) {
?>
<b><i><u>Отсутствуют</u></i></b>
<?php
  } else {
    $rpmsInfo = $repo->rpmsInfo(array_keys($deleted), $version1, ['Summary']);
    foreach ($deleted as $short=>$full) {
?>
    <li><b><?= $full?></b> - <i><?= $rpmsInfo[$short]['Summary']?></i></li>
<?php
    }
  }
?>
  </ul>
</li>
</ul>

