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
  //echo "<pre>STREAMS=" . print_r($streams, 1) . "</pre>\n";
  foreach ($streams as $stream) {
?>
<ul><h2>Поток: <?= $stream?></h2>
<?php
    foreach (repos::repoTypes() as $repoType) {
      $repo = new repo("acos/$arch/$stream", $repoType);
?>
  <li>
    <ul><h3>Тип репозитория: <?= $repoType?></h3>
<?php
      $refs = $repo->getRefs();
      foreach ($refs  as $ref) {
?>
    <li>
      <ul><h3>REF: <?= $ref?></h3>
        <li><a href='/v1/graph/?stream=<?= $stream?>&basearch=x86_64&repoType=<?= $repoType?>' target='graphREST'><?= $repoType?>-граф</a></li>
        <li>Коммиты:
       	  <ul>
<?php
        $commits = $repo->getCommits($ref);
        //echo "<pre>COMMITS=" . print_r($commits, 1) . "</pre>\n";
	$nCommits = count($commits);
	if ($nCommits  <= 0) continue;
	$commitIds = array_keys($commits);
	$lastCommitId = $commitIds[$nCommits-1];
	$lastVersion = $commits[$lastCommitId]['Version'];
        //echo "<pre>nCommits=$nCommits lastCommitId=$lastCommitId</pre>\n";
        foreach ($commits as $commitId=>$commit) {
          $version = $commit['Version'];
          $date = $commit['Date'];
?>
	        <li>Версия: <?= $version?><br>Дата создания: <?= $date?><br>ID: <?= $commitId?></li>
<?php 
        }
?>	  
  	      </ul>
	      <li><a href='/ostree/update/?ref=<?= $ref?>&version=<?= $lastVersion?>'>Обновить <?= $repoType?>-ветку <?= $ref?> версии <?= $lastVersion?></a></li>
          </ul>
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



