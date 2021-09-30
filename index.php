<?php
//phpinfo();
$rootdir = $_SERVER['DOCUMENT_ROOT'];
ini_set('include_path', "$rootdir/class");
require_once('repo.php');
require_once('repos.php');
require_once('altcosfile.php');

?>
<html>
<head>
<style>
.arch, .stream, .ref, .addSubtef {
  font-weight: bold;
  font-size: 14pt;
}

.warning {
  color: red;
  font-weight: bold;
  font-size: 14pt;
}
</style>
<?php
$SERVER_NAME = $_SERVER['SERVER_NAME'];
$Ref = false;
$os = key_exists('os',$_REQUEST) ? $_REQUEST['os'] : 'altcos';
$title[] = "Административный интерфейс OSTREE-потоков";
$title[] = repos::getOSName($os);

$listArchs = repos::listArchs();
$Arch = key_exists('arch', $_REQUEST) ? $_REQUEST['arch'] : (count($listArchs) == 1 ? $listArchs[0] : false);
?>
<title><?= implode('/', array_reverse($title))?></title>
</head>
<body>
<form action='./'>
<input type='hidden' name='os' value='<?= $os?>'/>
<h2><?= implode(" ", $title)?></h2>
<span class='arch'>
Архитектура:
<select name='arch'>
<?php
foreach ($listArchs as $arch) {
  $selected = ($arch === $Arch) ? 'selected' : '';
?>
  <option value='<?= $arch?>' <?= $selected?>><?= $arch?></option>
<?php
}
?>
</select>
</span>
<?php
$listStreams = repos::listStreams();
$Stream = (key_exists('stream', $_REQUEST)) ? $_REQUEST['stream'] : (count($listStreams) == 1 ? $listStreams[0] : false);
if ($Arch) {
?>
<span class='stream'>
Поток:
<select name='stream'>
<?php
  foreach ($listStreams as $stream) {
    $selected = ($stream == $Stream) ? 'selected' : '';
?>
  <option value='<?= $stream?>' <?= $selected?>><?= $stream?></option>
<?php
}
?>
</select>
</span>
<?php
  if ($Stream) {
    $repo = new repo("$os/$Arch/$Stream", 'bare');
    $refs = $repo->getRefs();
    $Ref = key_exists('ref', $_REQUEST) ? $_REQUEST['ref'] : (count($refs) == 1 ? $refs[0] : false);
    if (count($refs) == 0) {
      $Ref="$os/$Arch/$Stream";
    }
?>
<span class='ref'>
Ветка:
<select name='ref'>
<?php
    $altcosSubRefs = array_flip(altcosfile::getAcosSubRefs($Ref));
//     echo "<pre>ALTCOSSUBREFS=" . print_r($altcosSubRefs, 1) . "</pre>";
    $refExists = false;
    foreach ($refs as $ref) {
      if ($ref == $Ref) $refExists=true;
      if (key_exists($ref, $altcosSubRefs)) unset($altcosSubRefs[$ref]);
      $selected = ($ref == $Ref) ? 'selected' : '';
?>
  <option value='<?= $ref?>' <?= $selected?>><?= $ref?></option>
<?php
    }
    $altcosSubRefs = array_keys($altcosSubRefs);
//     echo "<pre>ALTCOSSUBREFS=" . print_r($altcosSubRefs, 1) . "</pre>";
?>
</select>
</span>
<?php
  }
}
?>
<button type='submit'>Отобразить</button>
</form>
<?php
if (!$Ref) exit(0);
if (!$refExists && strlen($Ref) > 0) {
  echo "<div class='warning'>Запрошенная ветка '$Ref' отсутствует в bare-репозитории</div>";
?>
<form action='ostree/createRef/' target='ostreeREST'>
<input name='ref' value='<?= $Ref?>' type='hidden' />
<button type='submit'>Создать ветку <?= $Ref?></button>
</form>
<?php
  exit(0);
}
?>

<title>Административный интерфейс OSTREE-потоков ALT Container OS</title>
<script>
function markAfter(input) {
  if (!input.checked) return;
  var inputs = document.getElementsByTagName('input');
  var n;
  for (n=0; n < inputs.length && inputs[n].value != input.value; n+=1);
  for (n=n+1; n < inputs.length; n+=1) {
    if (inputs[n].type='checkbox') {
      inputs[n].checked = true;
    }
  }

}
</script>
<?php
if (count($refs) > 0) {
?>
<p>
<!--form action='ostree/install/' target='ostreeREST'>
<input type='hidden' name='ref' value='<?= $Ref?>' />
<div class='addSubtef'>
Добавить подветку:
<br />
Имя подветки: <?= $Ref?>/<input type='input' name='subName' size='20'/>
<br />
Добавляемые пакеты:  <input type='input' name='pkgs' size='80'/>
<br />
<button type='submit'>Добавить</button>
</div>
</form-->
<?php
  if (count($altcosSubRefs) > 0) {
?>
<form action='/ostree/build/' target='ostreeREST'>
  <select name='altcosfileref'>
<?php
    foreach ($altcosSubRefs as $altcosSubRef) {
?>
    <option value='<?= $altcosSubRef?>'><?= $altcosSubRef?></option>
<?php
    }
?>
  </select>
  <button type='submit'>Построить</button>
</form>
<?php
  }
}
?>
<ul>
<?php
foreach (repos::repoTypes() as $repoType) {
  $repo = new repo($Ref, $repoType);
  $commits = $repo->getCommits();
  // echo "<pre>COMMITS=" . print_r($commits, 1) . "</pre>\n";
  $nCommits = count($commits);
  if ($nCommits == 0) {
    if ($repoType != 'bare') continue;
    $versionDates = $repo->versionDates();
    if (count($versionDates) > 0) {
?>
<div class='warning'>
В каталоге <?= $repo->varsDir?> есть каталоги /var по датам: <?= implode(', ', $versionDates)?>
<br />
Удалите их
</div>
<?php
    } else {
?>
<form action='ostree/createRef/' target='ostreeREST'>
<input name='ref' value='<?= $Ref?>' type='hidden' />
<button type='submit'>Создать ветку <?= $Ref?></button>
</form>
<?php
    }
  continue;
  }
?>
  <li>
    <ul><h3>Тип репозитория: <?= $repoType?></h3>
        <li><a href='http://<?= $SERVER_NAME?>/v1/graph/?stream=<?= $Stream?>&basearch=<?= $Arch?>&repoType=<?= $repoType?>' target='graphREST'><button><?= $repoType?>-граф</a></button></li>
        <li>Коммиты:
          <form action='/ostree/deleteCommits/' target='ostreeREST'>
          <input type='hidden' name='ref' value='<?= $Ref?>' />
          <input type='hidden' name='repoType' value='<?= $repoType?>' />
       	  <ul>
<?php
  $commitIds = array_keys($commits);
  $lastCommitId = $commitIds[$nCommits-1];
  $lastVersion = $commits[$lastCommitId]['Version'];
  //echo "<pre>nCommits=$nCommits lastCommitId=$lastCommitId</pre>\n";
  $nCommit = -1;
  foreach ($commits as $commitId=>$commit) {
    $nCommit += 1;
    $version = $commit['Version'];
    $date = $commit['Date'];
    $parent = @$commit['Parent'];
?>
		<li>
      <input type='checkbox' value='<?= $commitId?>' name='ids[]' onClick='markAfter(this)' />
		  <ul>ID: <?= $commitId?><br>Версия: <?= $version?><br>Дата создания: <?= $date?>
<?php
    if ($parent) { ?><br>Parent: <?= $parent?> <?php }
?>
		    <li
          ><a href='/ostree/fsck/?ref=<?= $Ref?>&repoType=<?= $repoType?>&commitId=<?= $commitId?>' target=ostreeREST
            ><button type='button'>Проверка целостности коммита</button
          ></a>
        </li>
		    <li>Содержание коммита
          <a href='/ostree/RPM/?ref=<?= $Ref?>&version=<?= $version?>' target=ostreeREST
            ><button type='button'>RPM</button>
          </a>
          <a href='/ostree/ls/?ref=<?= $Ref?>&repoType=<?= $repoType?>&commitId=<?= $commitId?>' target=ostreeREST
            ><button type='button'>OSTREE</button>
          </a>
          <a href='/ostree/lsTree/?ref=<?= $Ref?>&repoType=<?= $repoType?>&commitId=<?= $commitId?>' target=ostreeREST
            ><button type='button'>Файловая система</button>
          </a>
        </li>
        <li>Содержание каталога /var
          <a href='/ostree/lsvar/?ref=<?= $Ref?>&repoType=<?= $repoType?>&version=<?= $version?>' target=ostreeREST>
            <button type='button'>Файловая система</button>
          </a>
        </li>
<?php
    if ($nCommit > 0){
?>
		    <li>
          DIFF <?= $prevVersion?>
          <a href='/ostree/diffRPM/?ref=<?= $Ref?>&repoType=<?= $repoType?>&version1=<?= $version?>&version2=<?= $prevVersion?>' target=ostreeREST
            ><button type='button'>RPM</button>
          </a>
          <a href='/ostree/diff/?ref=<?= $Ref?>&repoType=<?= $repoType?>&commitId1=<?= $commitId?>&commitId2=<?= $prevCommitId?>' target=ostreeREST
            ><button type='button'>OSTREE</button>
          </a>
          <a href='/ostree/diffTree/?ref=<?= $Ref?>&repoType=<?= $repoType?>&commitId1=<?= $commitId?>&commitId2=<?= $prevCommitId?>' target=ostreeREST
            ><button type='button'>Файловая система</button>
          </a>
        </li>

<?php
    }
?>
        <!--li>
          <a href='/ostree/installer-altcos/?ref=<?= $Ref?>&commitId=<?= $commitId?>&version=<?= $version?>' target=ostreeREST>
            <button type='button'>Создать загрузочный QUEMU RAW диск</button>
          </a>
        </li>
        <li>
          <a href='/ostree/make_qcow2/?ref=<?= $Ref?>&commitId=<?= $commitId?>' target=ostreeREST>
            <button type='button'>Создать загрузочный QUEMU QCOW2 диск</button>
          </a>
        </li-->
        <li>
          <table border='1'>
            <tr>
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
            <tr>
<?php
      foreach ($imageTypes as $imageType) {
?>
              <td>
<?php
      $fullImage = $repo->getFullImageName($imageType, $version);
      $fullImageSize = $repo->getFullImageSize($imageType, $version);
      if ($fullImage) {
        $ref = "/ALTCOS/streams/$os/$arch/$stream/images/$imageType/$fullImage";
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
        $ref = "/ALTCOS/streams/$os/$arch/$stream/images/$imageType/$compressedImage";
?>
                  <a href='<?= $ref?>' title='<?= $compressedImage?>'><button type='button'>Скачать(<?= $compressedImageSize?>)</button></a>
<?php
      } else {
?>-<?php
      }
?>
                </td>
<?php
      }
?>
              </tr>
          </table>
        </li>
		  </ul>
    </li>
<?php
    $prevVersion = $version;
    $prevCommitId = $commitId;
  }
?>	  	<button type='submit'>Удалить отмеченные коммиты</button>
		</form>
  </ul>
<?php
  if ($repoType == 'bare') {
?>
	      <li><a href='/ostree/update/?ref=<?= $Ref?>&commitId=<?= $commitId?>' target=ostreeREST><button type='button'>Обновить bare-ветку <?= $Ref?> версии <?= $lastVersion?></button></a></li>
        <li><a href='/ostree/pullToArchive/?ref=<?= $Ref?>' target=ostreeREST><button type='button'>Скопировать  bare-репозиторий в archive-репозиторий</button></a></li>
<?php
  }
?>

</ul>
<?php
}




