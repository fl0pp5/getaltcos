<?php
$rootdir = $_SERVER['DOCUMENT_ROOT'];
ini_set('include_path', "$rootdir/class");
require_once('repo.php');
require_once('repos.php');

$ref = $_REQUEST['ref'];
$version = $_REQUEST['version'];

$repo = new repo($ref, 'bare');

$rpmList = $repo->listRPMs($version);
$rpmsInfo = $repo->rpmsInfo(array_keys($rpmList), $version, ['Summary']);
?>
<h2>Пакеты потока <?= $ref?> версии <?= $version?></h2>
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

