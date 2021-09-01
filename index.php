<?php
//phpinfo();
$rootdir = $_SERVER['DOCUMENT_ROOT'];
ini_set('include_path', "$rootdir/class");
require_once('repo.php');
?>

<html>
<head>
<title>Links</title>
</head>
<body>

<h1>TEST</h1>

<?php
$archs = repos::listArchs();
// echo "<pre>ARCHS=" . print_r($archs, 1) . "</pre>\n";

foreach ($archs as $arch) {
  $streams = repos::listStreams($arch);
  echo "<pre>STREAMS=" . print_r($streams, 1) . "</pre>\n";
  foreach ($streams as $stream) {
?>
<ul><h2>Поток: <?= $stream?></h2>
<?php
    foreach (repos::repoTypes() as $repoType) {
      $repo = new repo("acos/$arch/$stream", $repoType);
?>
  <ul><h3>Тип репозитория: <?= $repoType?>
<?php
      $refs = $repo->getRefs();
      foreach ($refs  as $ref) {
?>
    <li>
      <ul><h3>REF: <?= $ref?></h3>
        <li><a href='http://<?= $_SERVER['HTTP_HOST']?>/v1/graph/?stream=<?= $stream?>&basearch=x86_64' target='ostreeREST'>ГРАФ</a></li>
    <?php
        $commits = $repo->getCommits($ref);
        echo "<pre>COMMITS=" . print_r($commits, 1) . "</pre>\n";
?>
      </ul>
    </li>
    <?php
      }
    ?>
  </ul>
<?php
    }
?>
</ul>
<?php
  }
}
?>


<!--
<h2>Sisyphus</h2>

<h3>Граф</h3>

<ul>
<li><a href='http://getacos.altlinux.org/v1/graph/?stream=sisyphus&basearch=x86_64'>Graph</a></li>
<li><a href='http://getacos.altlinux.org/ostree/update/?ref=acos/x86_64/sisyphus&version=sisyphus.20210830.0.0'>Update</a></li>
</ul>

<h3>OSTREE</h3>




<h2>p10</h2>
<ul>
<li>
</ul>

<h1>ACOS Clients</h1>

<h2>Sisyphus</h2>
<ul>
<li>
</ul>

<h2>p10</h2>
<ul>
<li>
</ul>
-->
