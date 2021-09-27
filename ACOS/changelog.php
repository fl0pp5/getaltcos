<?php
$rootdir = $_SERVER['DOCUMENT_ROOT'];
ini_set('include_path', "$rootdir/class");
require_once('repo.php');
require_once('repos.php');

$branch = $_REQUEST['ref'];
$title = "История изменений потока $branch";
?>
<html lang='ru'>
<head>
<title><?= $title?></title>
</head>
<body>
<h1><?= $title?></h1>
<?php

$repo = new repo($branch, 'archive');
$repo->getCommits();
$commitIds = array_reverse(array_keys($repo->commits));
$nCommits = count($commitIds);
if ($nCommits <= 0) {
?>
<b><i><u>Версии отсутствуют</u></i></b>
<?php
  exit(0);
}
// echo "<pre>COMMITIDS=".print_r($commitIds, 1) . "</pre>";
for ($i=0; $i < $nCommits-1; $i++) {
  $commitId = $commitIds[$i];
  $commit = $repo->commits[$commitId];
  $version = $commit['Version'];
  $prevCommitId = $commitIds[$i+1];
  $prevCommit = $repo->commits[$prevCommitId];
  $prevVersion = $prevCommit['Version'];
//   echo "<pre>version=$version prevVersion=$prevVersion</pre>";
  $rpmDiff = $repo->cmpRPMs($version, $prevVersion);
//   echo "<pre>RPMDIFF=" . print_r($rpmDiff, 1) . "</pre>";
?>
<h2>Версия <?= $version?></h2>
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
    $rpmsInfo = $repo->rpmsInfo(array_keys($new), $version, ['Summary']);
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
    $rpmsInfo = $repo->rpmsInfo(array_keys($changed), $version, ['Summary']);
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
    $rpmsInfo = $repo->rpmsInfo(array_keys($deleted), $version, ['Summary']);
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
<?php
}

$firstCommitId = $commitIds[$nCommits-1];
$firstCommit = $repo->commits[$firstCommitId];
$firstVersion = $firstCommit['Version'];
$rpmList = $repo->listRPMs($firstVersion);
$rpmsInfo = $repo->rpmsInfo(array_keys($rpmList), $firstVersion, ['Summary']);

?>
<h2>Пакеты базовой версии <?= $firstVersion?></h2>
<ul>
<?php
// echo "<pre>RpmList" . print_r($rpmList, 1) . "</pre>";
foreach ($rpmList as $shortName => $fullName) {
?>
  <li><b><?= $fullName?></b> - <i><?= $rpmsInfo[$shortName]['Summary']?></i></li>
<?php
}
?>
</ul>
