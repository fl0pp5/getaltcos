<?php
// phpinfo();
$rootdir = $_SERVER['DOCUMENT_ROOT'];
ini_set('include_path', "$rootdir/class");
require_once('repo.php');
require_once('repos.php');
require_once('altcosfile.php');
require_once('butanefile.php');
require_once('vendor/autoload.php');
use Symfony\Component\Yaml\Yaml;
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

select {
  background-color: #AAFFAA
}


button.info {
  background-color: #AAFFAA
}

button.create {
  background-color: #FFFFAA
}

button.remove {
  background-color: #FFAAAA
}
</style>
<?php
$SERVER_NAME = $_SERVER['SERVER_NAME'];
$Ref = false;
$os = key_exists('os',$_REQUEST) ? $_REQUEST['os'] : 'altcos';
$MIRRORURL = getenv("MIRRORURL");
if (strlen(trim($MIRRORURL)) == 0) {
  $MIRRORURL = 'https://altcos.altlinux.org/ALTCOS/streams';
}
$MIRRORSTREAMS = [];
foreach (explode(',', getenv("MIRRORSTREAMS")) as $mirror) {
  $MIRRORSTREAMS[] = trim($mirror);
}
// echo "<pre>MIRRORURL=$MIRRORURL MIRRORSTREAMS=". print_r($MIRRORSTREAMS, 1) . "</pre>";
$MIRRORBARE = getenv("MIRRORBARE");

$maxTimeOfDate = 0;

$title[] = "Административный интерфейс OSTREE-потоков";
$title[] = repos::getOSName($os);

$listArchs = repos::listArchs('altcos');
$Arch = key_exists('arch', $_REQUEST) ? $_REQUEST['arch'] : (count($listArchs) == 1 ? $listArchs[0] : false);
$selected = $Arch ? '' : 'selected';
?>
<title><?= implode('/', array_reverse($title))?></title>
<script>
function submitStreamForm(changedSelect) {
  var form = document.getElementById('streamForm');
  var selects = form.getElementsByTagName('SELECT');
  var i = 0;
  for (i = 0; i < selects.length && selects[i].id != changedSelect.id; i += 1);
  for (i += 1; i < selects.length; i+= 1) {
    select = selects[i];
    select.selectedIndex = 0;
  }
  form.submit();
}

function getCurDate() {
  var d = new Date()
  var curYear = d.getFullYear().toString(),
    curMonth = d.getMonth() +1,
    curDay = d.getDate();
  curMonth = (curMonth < 10) ? '0' + curMonth : '' + curMonth;
  curDay = (curDay < 10) ? '0' + curDay : '' + curDay;
  return curYear + curMonth + curDay;
}

function notCorrectDate(date, prevDate) {
  if (date.length != 8) {
    alert('Неверная длина даты ' + date + ' ' + date.length + ' символов');
    return true;
  }
  if (isNaN(parseInt(date)) || parseInt(date).toString() != date ) {
    alert('Нечисловой формат даты ' + date);
    return true;
  }
  var curDate = getCurDate();
  if (curDate < date) {
    alert('Заданная дата ' + date + ' после текущей даты ' + curDate)
    return true;
  }
  return false;
}

function updateRepo(button) {
//   alert(button);
  var form = button.parentNode;
  var inputs = form.getElementsByTagName('input');
  var date = inputs[2].value;
  var prevDate = inputs[2].getAttribute('prevValue');
  if (notCorrectDate(date, prevDate)) return;
  if (date < prevDate) {
    alert('Заданная дата '+ date + ' раньше даты последнего коммита ' + prevDate);
    return;
  }
  var curDate = getCurDate();
  if (date > prevDate) {
    inputs[3].value = '0';
    inputs[4].value = '0';
    alert('Дата изменилась, устанавливаем major и minor версии в ноль');
  } else {
    var major = inputs[3].value;
    var prevMajor = inputs[3].getAttribute('prevValue');
    if (isNaN(parseInt(major)) || parseInt(major).toString() != major ) {
      alert('Нечисловой формат major версии ' + major);
      return;
    }
    if (major < prevMajor) {
      alert('Заданный major '+ major + ' меньше последнего major ' + prevMajor);
      return;
    }
    if (major > prevMajor) {
      if (inputs[4].value != '0') {
        inputs[4].value = '0';
        alert('Major изменился, устанавливаем minor версию в ноль');
      }
    } else {
      var minor = inputs[4].value;
      var prevMinor = inputs[4].getAttribute('prevValue');
      if (isNaN(parseInt(minor)) || parseInt(minor).toString() != minor ) {
        alert('Нечисловой формат minor версии ' + minor);
        return;
      }
      if (minor < prevMinor) {
        alert('Заданный minor '+ minor + ' не больше последнего minor ' + prevMinor);
        return;
      }
    }
  }
//   alert('date=' + date + ' prevDate=' + prevDate + ' major=' + major + ' prevMajor=' + prevMajor + ' minor=' + minor + ' prevMinor=' + prevMinor);
  form.submit();
}

</script>
</head>
<body>
<form action='./' id='streamForm'>
<input type='hidden' name='os' value='<?= $os?>'/>
<h2><?= implode(" ", $title)?></h2>
<span class='arch'>
Архитектура:
<select name='arch' id='selectArch' onchange="submitStreamForm(this)">
  <option value='' id='selectArch' <?= $selected?>></option>
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
$listStreams = repos::listStreams('altcos', $Arch, $MIRRORSTREAMS);
$Stream = (key_exists('stream', $_REQUEST)) ? $_REQUEST['stream'] : (count($listStreams) == 1 ? $listStreams[0] : false);
$selected = $Stream ? '' : 'selected';
if ($Arch) {
?>
<span class='stream'>
Поток:
<select name='stream' id='selectStream' onchange="submitStreamForm(this)">
  <option value='' <?= $selected?>></option>
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
    if (count($MIRRORSTREAMS) > 0) {
      foreach ($MIRRORSTREAMS as $mirrorstream) {
        $path = explode('/', $mirrorstream);
        if ($path[0] == $os && $path[1] == $Arch && strtolower($path[2] == $Stream)) {
          $refs[] = $mirrorstream;
        }
      }
    }
    $Ref = key_exists('ref', $_REQUEST) ? $_REQUEST['ref'] : (count($refs) == 1 ? $refs[0] : false);
    if (count($refs) == 0) {
      $Ref="$os/$Arch/$Stream";
    }
  $selected = $Ref ? '' : 'selected';
?>
<span class='ref'>
Ветка:
<select name='ref' id='selectRef' onchange="submitStreamForm(this)">
  <option value='' <?= $selected?>></option>
<?php
    $altcosSubRefs = [];
    if ($repo->haveConfig()) {
      $altcosSubRefs = array_flip(altcosfile::getAcosSubRefs($Ref));
    }
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
<button type='submit' class='info'>Отобразить</button>
</form>
<?php
if (!$Ref) exit(0);
$mirrorMode = in_array($Ref, $MIRRORSTREAMS);
if ($mirrorMode && !$repo->haveConfig()) {
?>
<div class='warning'>Запрошенная ветка '<?= $Ref?>' еще не отзеркалирована</div>
<form action='ostree/mirror/' target='ostreeREST'>
<input name='url' value='<?= $MIRRORURL?>' type='hidden' />
<input name='streams' value='<?= $Ref?>' type='hidden' />
<button type='submit' class='create'>Создать зеркало ветки <?= $Ref?></button>
</form>
<?php
  exit(0);
}

if (!$refExists && strlen($Ref) > 0) {
  echo "<div class='warning'>Запрошенная ветка '$Ref' отсутствует в bare-репозитории</div>";
?>
<form action='ostree/createRef/' target='ostreeREST'>
<input name='ref' value='<?= $Ref?>' type='hidden' />
<button type='submit' class='create'>Создать ветку <?= $Ref?></button>
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
  <select name='ref'>
<?php
    foreach ($altcosSubRefs as $altcosSubRef) {
?>
    <option value='<?= $altcosSubRef?>'><?= $altcosSubRef?></option>
<?php
    }
?>
  </select>
  <button type='submit'  class='create'>Построить</button>
</form>
<?php
  }
}
?>
<ul>
<h2>Ветка <?= $Ref?></h2>
<?php
foreach (repos::repoTypes($mirrorMode) as $repoType) {
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
<button type='submit' class='create'>Создать ветку <?= $Ref?></button>
</form>
<?php
    }
  continue;
  }
?>
  <li>
    <ul><h3>Тип репозитория: <?= $repoType?> <?= $mirrorMode? "(зеркало $MIRRORURL/$Ref)":'' ?></h3>
        <li><a href='http://<?= $SERVER_NAME?>/<?= $repoType?>/v1/graph/?stream=<?= $Stream?>&basearch=<?= $Arch?>' target='graphREST'><button  class='info'><?= $repoType?>-граф</a></button></li>
        <li>Коммиты:
          <form action='/ostree/deleteCommits/' target='ostreeREST'>
          <input type='hidden' name='ref' value='<?= $Ref?>' />
          <input type='hidden' name='repoType' value='<?= $repoType?>' />
       	  <ul>
<?php
  $parentRef = repos::parentRef($Ref);
  $commitIds = array_keys($commits);
  $lastCommitId = $commitIds[$nCommits-1];
  $lastVersion = $commits[$lastCommitId]['Version'];
  //echo "<pre>nCommits=$nCommits lastCommitId=$lastCommitId</pre>\n";
  $nCommit = -1;
  foreach ($commits as $commitId=>$commit) {
    $nCommit += 1;
    $version = $commit['Version'];
    $date = $commit['Date'];
    $timeOfDate = strtotime($date);
    if ($maxTimeOfDate < $timeOfDate) $maxTimeOfDate = $timeOfDate;
    $parent = @$commit['Parent'];
?>
		<li>
      <input type='checkbox' value='<?= $commitId?>' name='ids[]' onClick='markAfter(this)' />
		  <ul id='<?= $commitId?>'>ID: <?= $commitId?><br>Версия: <b><?= $version?></b><br>Дата создания: <b><?= $date?></b>
<?php
    if ($parent) { ?><br>Parent: <?= $parent?> <?php }
    if (!repos::isBaseRef($Ref)) {
      $metaData = $repo->getMetaData($commitId, ['parentCommitId', 'parentVersion']);
//       echo "<pre>MetaData=" . print_r($metaData, 1) . "</pre>";
?>
      <br>Родительская версия ветки <b><i><?= $parentRef?></i></b>: <b><a href='./?os=<?= $os?>&arch=<?= $Arch?>&stream=<?= $Stream?>&ref=<?= $parentRef?>#<?= $metaData['parentCommitId']?>' target='parentREST'><?= $metaData['parentVersion']?></a></b>
<?php
    }
?>
		    <li
          ><a href='/ostree/fsck/?ref=<?= $Ref?>&repoType=<?= $repoType?>&commitId=<?= $commitId?>' target=ostreeREST
            ><button type='button' class='info'>Проверка целостности коммита</button
          ></a>
        </li>
		    <li>Содержание коммита
          <a href='/ostree/RPM/?ref=<?= $Ref?>&version=<?= $version?>' target=ostreeREST
            ><button type='button' class='info'>RPM</button>
          </a>
          <a href='/ostree/ls/?ref=<?= $Ref?>&repoType=<?= $repoType?>&commitId=<?= $commitId?>' target=ostreeREST
            ><button type='button' class='info'>OSTREE</button>
          </a>
          <a href='/ostree/lsTree/?ref=<?= $Ref?>&repoType=<?= $repoType?>&commitId=<?= $commitId?>' target=ostreeREST
            ><button type='button' class='info'>Файловая система</button>
          </a>
        </li>
        <li>Содержание каталога /var
          <a href='/ostree/lsvar/?ref=<?= $Ref?>&repoType=<?= $repoType?>&version=<?= $version?>' target=ostreeREST>
            <button type='button' class='info'>Файловая система</button>
          </a>
        </li>
<?php
    if ($nCommit > 0){
?>
		    <li>
          DIFF <?= $prevVersion?>
          <a href='/ostree/diffRPM/?ref=<?= $Ref?>&repoType=<?= $repoType?>&version1=<?= $version?>&version2=<?= $prevVersion?>' target=ostreeREST
            ><button type='button' class='info'>RPM</button>
          </a>
          <a href='/ostree/diff/?ref=<?= $Ref?>&repoType=<?= $repoType?>&commitId1=<?= $commitId?>&commitId2=<?= $prevCommitId?>' target=ostreeREST
            ><button type='button' class='info'>OSTREE</button>
          </a>
          <a href='/ostree/diffTree/?ref=<?= $Ref?>&repoType=<?= $repoType?>&commitId1=<?= $commitId?>&commitId2=<?= $prevCommitId?>' target=ostreeREST
            ><button type='button' class='info'>Файловая система</button>
          </a>
        </li>

<?php
    }
?>
        <!--li>
          <a href='/ostree/installer-altcos/?ref=<?= $Ref?>&commitId=<?= $commitId?>&version=<?= $version?>' target=ostreeREST>
            <button type='button' class='info'>Создать загрузочный QUEMU RAW диск</button>
          </a>
        </li>
        <li>
          <a href='/ostree/make_qcow2/?ref=<?= $Ref?>&commitId=<?= $commitId?>' target=ostreeREST>
            <button type='button' class='info'>Создать загрузочный QUEMU QCOW2 диск</button>
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
        $dir = repos::refToDir($Ref);
        $ref = "/ALTCOS/streams/$dir/images/$imageType/$fullImage";
?>
                <a href='<?= $ref?>' title='<?= $fullImage?>'><button type='button' class='info'>Скачать(<?= $fullImageSize?>)</button></a>
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
        $dir = repos::refToDir($Ref);
        $ref = "/ALTCOS/streams/$dir/images/$imageType/$compressedImage";
?>
                  <a href='<?= $ref?>' title='<?= $compressedImage?>'><button type='button' class='info'>Скачать(<?= $compressedImageSize?>)</button></a>
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
  if (!$mirrorMode):
?>	  	<button type='submit' class='remove'>Удалить отмеченные коммиты</button>
<?php endif; ?>
		</form>
  </ul>
<?php
  if ($mirrorMode) {
    if ($repoType == 'archive') {
?>
      <li><a href='/ostree/mirror/?url=<?= $MIRRORURL?>&streams=<?= $Ref?>' target=ostreeREST><button type='button' class='create'>Отзеркалировать</button></a></li><?php

    }
  } else {
    if ($repoType == 'bare') {
      $newALTCOSfile = false;
      $newBUTANEfile = false;
      if (!repos::isBaseRef($Ref)) {
        $altcosfile = new altcosfile($Ref);
//         echo "<pre>ALTCOSfile=" . print_r($altcosfile, 1) . "</pre>";
        if ($altcosfile->error) {
          echo "<b>". $altcosfile->error . "</b>";
        } else {
          $newALTCOSfile = ($maxTimeOfDate < $altcosfile->filemtime);
          $message = ($newALTCOSfile) ? "<b>ALTCOSfile.yml был обновлен " . strftime("%Y-%m-%d %H:%M:%S %z", $altcosfile->filemtime) . "</b>" :
            "ALTCOSfile.yml не обновлялся после последный сборки " . strftime("%Y-%m-%d %H:%M:%S %z", $maxTimeOfDate);
?>
        <?= $message?>
        <a href="<?= $altcosfile->path?>" target=ostreeREST><button>Посмотреть</button></a>
<?php   } ?>
        <br><form action='/ostree/updateALTCOSFILE.php' method='POST' enctype='multipart/form-data' target=ostreeREST
          ><input type='file' name='ALTCOSfile'
          /><input type='hidden' name='ref' value='<?= $Ref?>'
          /><button>Обновить</button
        ></form>
<?php
        echo "<br>";
        $butaneName = $altcosfile->getButaneFile();
        if ($altcosfile->haveButaneFile()) {
          $butanefile = new butanefile($Ref);
          if ($butanefile->error) {
            echo "<b>". $butanefile->error . "</b>";
          } else {
            $newBUTANEfile = ($maxTimeOfDate < $butanefile->filemtime);
            $message = ($newBUTANEfile) ? "<b>BUTANEfile.yml был обновлен " . strftime("%Y-%m-%d %H:%M:%S %z", $butanefile->filemtime) . "</b>" :
              "Butanefile $butaneName не обновлялся после последный сборки " . strftime("%Y-%m-%d %H:%M:%S %z", $maxTimeOfDate);
?>
          <?= $message?>
          <a href="<?= $butanefile->path?>" target=ostreeREST><button>Посмотреть</button></a>
<?php
          }
      } else {
        echo "<b>Butane-файл отсутствует</b>";
      }
?>
          <br><form action='/ostree/updateBUTANEFILE.php' method='POST' enctype='multipart/form-data' target=ostreeREST
            ><input type='file' name='BUTANEfile'
            /><input type='hidden' name='ref' value='<?= $Ref?>'
            /><button>Обновить</button
          ></form>
<?php
      $parentRepo = new repo($parentRef);
      $parentRepo->getCommits();
      $metaParentCommitId = $metaData['parentCommitId'];
      if ($parentRepo->lastCommitId != $metaParentCommitId || $newALTCOSfile || $newBUTANEfile) {
        $parentVersion = $parentRepo->getCommitVersion($parentRepo->lastCommitId);
?>
	      <li
          ><a href='/ostree/build/?ref=<?= $Ref?>' target=ostreeREST
            ><button type='button' class='create'
              >Обновить дочернюю bare-ветку <b><?= $Ref?></b> версии <b><?= $lastVersion?></b> от основной родительской ветки версии <b><?= $parentVersion?></b>
              </button></a
        ></li><?php
      }
      } else {  //Базовая ветка
        $nextVersion = repos::nextMinorVersion($lastVersion);
        list($stream_, $date_, $major_, $minor_) = explode('.', $nextVersion);
?>
	      <li>
          <form action='/ostree/update/' target='ostreeREST'>
            <input type='hidden' name='ref' value='<?= $Ref?>' />
            <input type='hidden' name='commitId' value='<?= $commitId?>' />
            <button type='button' class='create' onClick='updateRepo(this)'>Обновить bare-ветку <?= $Ref?> до версии</button>
            <?= $stream_?>.
            <input name='date' value='<?= $date_?>' prevValue='<?= $date_?>' size='6' />.
            <input name='major' value='<?= $major_?>' prevValue='<?= $major_?>' size='1'/>.
            <input name='minor' value='<?= $minor_?>'  prevValue='<?= $minor_?>' size='1'/>
          </form>
          <!--a href='/ostree/update/?ref=<?= $Ref?>&commitId=<?= $commitId?>' target=ostreeREST
            ><button type='button' class='create'>Обновить bare-ветку <?= $Ref?> версии <?= $lastVersion?></button
          ></a-->
        </li>
<?php
      }
?>
        <li><a href='/ostree/pullToArchive/?ref=<?= $Ref?>&archiveName=archive' target=ostreeREST><button type='button' class='create'>Скопировать  bare-репозиторий в archive-репозиторий</button></a></li>
        <li><a href='/ostree/pullToArchive/?ref=<?= $Ref?>&archiveName=barearchive' target=ostreeREST><button type='button' class='create'>Скопировать  bare-репозиторий в barearchive-репозиторий</button></a></li>
<?php
    }
  }

?>

</ul>
<?php
}




