<?php
$rootdir = $_SERVER['DOCUMENT_ROOT'];
ini_set('include_path', "$rootdir/class");
require_once('repo.php');
require_once('repos.php');

$branch = $_REQUEST['ref'];
?>
<h1>История изменений потока <?= $branch?></h1>
<?php

$repo = new repo($branch, 'archive');
$repo->getCommits();
$commitIds = array_reverse(array_keys($repo->commits));
$nCommits = count($commitIds);
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
Отсутствуют
<?php
  } else {
    foreach ($new as $short=>$full) {
?>
    <li><?= $full?></li>
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
Отсутствуют
<?php
  } else {
    foreach ($changed as $short=>$list) {
      $full1 = $list[0];
      $full2 = $list[1];
?>
    <li><?= $full1?> =&gt; <?= $full2?></li>
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
Отсутствуют
<?php
  } else {
    foreach ($deleted as $short=>$full) {
?>
    <li><?= $full?></li>
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
?>
<h2>Пакеты базовой версия <?= $firstVersion?></h2>
<ul>
<?php
// echo "<pre>RpmList" . print_r($rpmList, 1) . "</pre>";
foreach ($rpmList as $rpm) {
?>
  <li><?= $rpm?></li>
<?php
}
?>
</ul>
