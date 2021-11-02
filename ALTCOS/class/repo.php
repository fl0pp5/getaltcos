<?php
require_once "repos.php";
class repo {
  function __construct($ref, $repoType='bare', $repoDir=false) {
    if (!$repoDir) {
      $repoDir = $repoType;
    }
    $this->repoType = $repoType;
    $this->ref = $ref;
    $this->refs = false;
    $this->refRepoDir = $_SERVER['DOCUMENT_ROOT'] . "/ALTCOS/streams/" . repos::refRepoDir($ref);
    $this->refDir = $_SERVER['DOCUMENT_ROOT'] . "/ALTCOS/streams/" . repos::refToDir($ref);
    $this->repoDir = $this->refRepoDir . "/$repoDir/repo";
    $this->rootsDir = $this->refDir . "/roots";
    $this->varsDir = $this->refDir . "/vars/";
    $this->commits = false;
    $this->lastCommit = false;
    $this->lastCommitId = false;
    $this->imagesTree = $this->getImagesTree();
  }

  function haveConfig() {
    $configFile = $this->repoDir."/config";
    $ret = file_exists($configFile);
//    echo "<pre>CONFIGFILE=$configFile<br>\n";
    return $ret;
  }

  function init() {
    $cmd = "sudo mkdir -p " .  $this->repoDir .' 2>&1';;
    $output = [];
//     echo "<pre>CMD=$cmd</pre>\n";
    exec($cmd, $output);
//     echo "<pre>MKDIR=" . print_r($output, 1) . "</pre>\n";
    $cmd = "sudo ostree init --mode=" . $this->repoType . " --repo=" .  $this->repoDir .' 2>&1';;
    $output = [];
//     echo "<pre>CMD=$cmd</pre>\n";
    exec($cmd, $output);
//     echo "<pre>INIT=" . print_r($output, 1) . "</pre>\n";
  }

  function getRefs() {
    if (is_array($this->refs)) {
      return $this->refs;
    }
    $cmd = "ostree refs --repo=".$this->repoDir;
    $output = [];
//    echo "<pre>CMD=$cmd</pre>\n";
    exec($cmd, $output);
//    echo "<pre>REFS=" . print_r($output, 1) . "</pre>\n";
    $this->refs = $output;
    return $output;
  }

  function deleteRef() {
    $ref = $this->ref;
    $cmd = "sudo ostree refs --delete $ref --repo=".$this->repoDir .' 2>&1';;
    $output = [];
    echo "<pre>CMD=$cmd</pre>\n";
    exec($cmd, $output);
    echo "<pre>REFS=" . print_r($output, 1) . "</pre>\n";
    return $output;
  }

  function createRef($commitId) {
    $ref = $this->ref;
    $cmd = "sudo ostree refs --create=$ref $commitId --repo=".$this->repoDir . ' 2>&1';
    $output = [];
    echo "<pre>CMD=$cmd</pre>\n";
    exec($cmd, $output);
    echo "<pre>REFS=" . print_r($output, 1) . "</pre>\n";
    return $output;
  }

  function getCommits() {
    if (is_array($this->commits)) {
      return $this->commits;
    }
    $ref = $this->ref;
    $cmd = "ostree log $ref --repo=". $this->repoDir;
    //echo "CMD=$cmd<br>\n";
    exec($cmd, $output);
    #print_r($output);
    $commits = [];
    $commit = [];
    for ($i = 0; $i < count($output); $i++) {
      $str = trim($output[$i]);
      #echo $str;
      if (strlen($str) ==0 ) {
        $commits[$commitId] = $commit;
        $commit = [];
        continue;
      }
      if (substr($str, 0, 6) == 'commit') {
        $commitId = trim(substr($str,6));
        $commit['commit'] = $commitId;
        continue;
      }
      if (substr($str, 0, 7) == 'Parent:') {
        $parent = trim(substr($str,7));
        $commit['Parent'] = $parent;
        continue;
      }
      if (substr($str, 0, 5) == 'Date:') {
        $date = trim(substr($str,5));
        $commit['Date'] = $date;
        continue;
      }
      if (substr($str, 0, 8) == 'Version:') {
        $version = trim(substr($str, 8));
        $commit['Version'] = $version;
        continue;
      }
    }
    uasort($commits, 'repo::cmpByDate');
    $this->commits = $commits;
    $commitsIds = array_keys($commits);
    $nCommits = count($commitsIds);
    if ($nCommits > 0) {
      $this->lastCommitId = $commitsIds[$nCommits - 1];
      $this->lastCommit = $commits[$this->lastCommitId];
    }
    return $commits;
  }

  function getCommitParent($commitId) {
    return $this->commits[$commitId]['Parent'];
  }

  function getCommitDate($commitId) {
    return $this->commits[$commitId]['Date'];
  }

  function getCommitVersion($commitId) {
    return $this->commits[$commitId]['Version'];
  }


  function deleteCommit($commitId) {
    $cmd = "sudo ostree prune --delete-commit $commitId --repo=".$this->repoDir . ' 2>&1';;
    $output = [];
    echo "<pre>CMD=$cmd</pre>\n";
    exec($cmd, $output);
    echo "<pre>REFS=" . print_r($output, 1) . "</pre>\n";
    $cmd = "sudo rm -f " . $this->varsDir . "/$commitId;";
    echo "<pre>CMD=$cmd</pre>\n";
    exec($cmd);
    return $output;
  }

  function lastCommit() {
    if (!$this->commits) {
      $this->getCommits($ref);
    }
    return $this->lastCommit;
  }

  function rpmList($version) {
    if (!$this->commits) {
      $this->getCommits($this->ref);
    }
    $versionDir = $this->varsDir . '/' . repos::versionVarSubDir($version);
    $cmd = "rpm -qa --dbpath=$versionDir/var/lib/rpm";
//     echo "CMD=$cmd<br>\n";
    exec($cmd, $output);
    $ret = [];
    foreach ($output as $rpm) {
      if (strlen(trim($rpm)) == 0) continue;
      $ret[] = $rpm;
    }
//     print_r($ret);
    return $ret;
  }

  function fsck($commitId) {
    $cmd = "sudo ostree fsck $commitId  --repo=". $this->repoDir . " 2>&1";
    //echo "CMD=$cmd<br>\n";
    exec($cmd, $output);
    //print_r($output);
    return $output;
  }

  function ls($commitId, $flags='-X') {
    $cmd = "sudo ostree ls -R $commitId $flags --repo=". $this->repoDir;
    echo "$cmd<br>\n";
    exec($cmd, $output);
    //print_r($output);
    return $output;
  }

  function checkout($commitId, $replace=false) {
    if (!is_dir($this->rootsDir)) {
      $cmd = "sudo mkdir -p " .  $this->rootsDir .' 2>&1';;
      $output = [];
//       echo "<pre>CMD=$cmd</pre>\n";
      exec($cmd, $output);
//       echo "<pre>MKDIR=" . print_r($output, 1) . "</pre>\n";
    }
    $checkoutDir = $this->rootsDir . "/$commitId";
    if (is_dir($checkoutDir)) {
      if ($replace) {
        $cmd = "sudo rm -rf $checkoutDir 2>&1";;
        $output = [];
//         echo "<pre>CMD=$cmd</pre>\n";
        exec($cmd, $output);
//         echo "<pre>MKDIR=" . print_r($output, 1) . "</pre>\n";
      } else {
        return;
      }
    }
    $cmd = "sudo ostree checkout $commitId " . $checkoutDir . " --repo=". $this->repoDir;
//     echo "<br>$cmd<br>\n";
    exec($cmd, $output);
    //print_r($output);
  }

    function diff($commitId1, $commitId2) {
    $cmd = "sudo ostree diff $commitId1 $commitId2 --repo=". $this->repoDir;
    echo "$cmd<br>\n";
    exec($cmd, $output);
    //print_r($output);
    return $output;
  }


  /*
   * Возвращает число дат вариантов ветки в директории vars/
   * 20210830, aaa, 20210910 => [ 20210830, 20210910 ]
   */
  function versionDates() {
    $ref = $this->ref;
    $refVersionDatesDir =  $this->varsDir;
    $fd = dir($refVersionDatesDir);
    $ret = [];
    while ($entry = $fd->read()) {
      if (strlen($entry) == 8 &&
          intval(substr($entry, 0, 4)) > 2020 &&
          intval(substr($entry, 4, 2)) > 0 && intval(substr($entry, 4, 2)) < 13 &&
          intval(substr($entry, 6, 2)) > 0 && intval(substr($entry, 6, 2)) < 32) {
        $ret[] = $entry;
      }
    }
    return $ret;
  }

  function deleteVarDir($version) {
    $versionVarSubDir = repos::versionVarSubDir($version);
    $versionDir = $this->varsDir . "/$versionVarSubDir" ;
    echo "<br>deleteVarDir:: versionDir=$versionDir<br>";
    if (count(explode('/', $versionDir) > 9)) {
      $cmd = "sudo rm -rf $versionDir 2>&1";
      echo "<br>$cmd<br>\n";
      exec($cmd, $output);
      print_r($output);
      $path = explode('/', $versionVarSubDir);
      $revPath = array_reverse($path);
      //Если версия date/x/0 удалить date/x, если версия date/0/0 удалить date
      for ($i = 0; $i < 2; $i++) {
        echo "<pre>SUBDIR=" . $revPath[$i] . "</pre>";
        if ($revPath[$i] == '0') {
          array_pop($path);
          $Dir = $this->varsDir . '/' . implode('/', $path);
          $cmd = "sudo rm -rf $Dir 2>&1";
          echo "<br>$cmd<br>\n";
          exec($cmd, $output);
          print_r($output);
        } else {
          break;
        }
      }
    }
  }

  /* Вывести дерево доступеых образов
   */

  function getImagesTree() {
    $imagesDir = $this->refDir . "/images";
//     echo "<pre>imagesDir=$imagesDir</pre>";
   $ret = [];
    if (!is_dir($imagesDir)) return;
    $fd = dir($imagesDir);
    while ($imageType = $fd->read()) {
      $imageTypeDir = "$imagesDir/$imageType";
//       echo "<pre>imageTypeDir=$imageTypeDir</pre>";
      if (substr($imageType, 0, 1) == '.') continue;
      if (!is_dir($imageTypeDir)) continue;
      $fd1 = dir($imageTypeDir);
      while ($image = $fd1->read()) {
//         echo "<pre>image=$image</pre>";
        $path = explode('.', $image);
        $nPath = count($path);
//         echo "<pre>nPath=$nPath</pre>";
        if ($nPath != 5 &&  $nPath != 6) continue;
        $subPath = $path[0];
        $date = $path[1];
        $major = $path[2];
        $minor = $path[3];
        $suffix = $path[4];
        $compressed = ($nPath == 6);
        $compessSuffix = false;
        if ($compressed) {
          $index = 'compressed';
          $compessSuffix = $path[5];
        } else {
          $index = 'full';
        }
        $ret[$imageType][$date][$major][$minor][$index] = [
          'image'=>$image,
          'imageType'=>$imageType,
          'date'=>$date,
          'major'=>$major,
          'minor'=>$minor,
          'compessSuffix'=>$compessSuffix,
          'compressed'=>$compressed
        ];
      }
    }
    return $ret;
  }

  function getImagesTypes($asc=true) {
    $ret = array_keys($this->imagesTree);
//     echo "<pre>TYPES=" . print_r($ret, 1) . "</pre>\n";
    sort($ret);
    if (!$asc) $ret = array_reverse($ret);
    return $ret;
  }

  function getFullImageName($imageType, $version) {
    list($stream, $date, $major, $minor) = explode('.', $version);
    $ret = @$this->imagesTree[$imageType][$date][$major][$minor]['full']['image'];
    return $ret;
  }

  function getCompressedImageName($imageType, $version) {
    list($stream, $date, $major, $minor) = explode('.', $version);
    $ret = @$this->imagesTree[$imageType][$date][$major][$minor]['compressed']['image'];
    return $ret;
  }


  function getFullImageSize($imageType, $version) {
    list($stream, $date, $major, $minor) = explode('.', $version);
    $imageName = @$this->imagesTree[$imageType][$date][$major][$minor]['full']['image'];
    if (!$imageName) return 0;
    $file = $this->refDir . "/images/$imageType/$imageName";
    if (!file_exists($file)) return 0;
    $size = filesize($file);
    $ret = repo::sizeToText($size);
    return $ret;
  }

  function getCompressedImageSize($imageType, $version) {
    list($stream, $date, $major, $minor) = explode('.', $version);
    $imageName = @$this->imagesTree[$imageType][$date][$major][$minor]['compressed']['image'];
    if (!$imageName) return 0;
    $file = $this->refDir . "/images/$imageType/$imageName";
    if (!file_exists($file)) return 0;
    $size = filesize($file);
    $ret = repo::sizeToText($size);
    return $ret;
  }

  /*
   * Получить список RPM-пакетов в версии
   */
  function listRPMs($version) {
    $versionDir = repos::versionVarSubDir($version);
    $path = $this->varsDir . "/$versionDir/var/lib/rpm/";
    $cmd = "rpm -qa --dbpath=$path | sort";
//     echo "<pre>CMD=$cmd</pre>\n";
    $output = [];
    exec($cmd, $output);
    $ret = [];
    foreach ($output as $fullName) {
      $shortName = repos::fullRPMNameToShort($fullName);
      $ret[$shortName] = $fullName;
    }
    return $ret;
  }

  /*
   * Получить информацию по переданному списку пакетов
   * !! ПОКА НЕ ОБРАБАТЫВАЮТСЯ МНОГОСТРОЧНЫЕ ОПИСАТЕЛИ и $listFields=false не выводит ни одного поля
   */
  function rpmsInfo($list, $version, $listFields=[]) {
    $versionDir = repos::versionVarSubDir($version);
    $path = $this->varsDir . "/$versionDir/var/lib/rpm/";
    $list = implode(' ', $list);
    $cmd = "export LANG=C;rpm -qi --dbpath=$path $list";
//      echo "<pre>CMD=$cmd</pre>\n";
    $output = [];
    exec($cmd, $output);
//     echo "<pre>RPMSLIST=" . print_r($output, 1) . "</pre>\n";
    $ret = [];
    $first = true;
    foreach ($output as $line) {
      $path = explode(':', $line, 2);
      if (count($path) < 2) {
        continue;
      }
      list($fieldName, $value) = $path;
      $fieldName = trim($fieldName);
//       echo "<pre>fieldName='$fieldName' value=$value listFields=". print_r($listFields, 1) . "</pre>";
      if ($fieldName == 'Name') {
        $pkgName = trim($value);
//         echo "<pre>Name: $pkgName</pre>";
        if ($first) {
          $first = false;
        } else {
          $ret[$pkgName] = [];
        }
      } else {
        if (in_array($fieldName, $listFields)) {
//           echo "<pre>FIELDNAME=$fieldName VALUE=$value</pre>";
          $ret[$pkgName][$fieldName] = $value;
        }
      }
    }
//     echo "<pre>RET=".print_r($ret, 1)."</pre>";
    return $ret;
  }


  /*
   * Сравнить список RPM-файлов различный версий
   */
  function cmpRPMs($version1, $version2) {
    $version1Dir = repos::versionVarSubDir($version1);
    $version2Dir = repos::versionVarSubDir($version2);

    $path1 = $this->varsDir . "/$version1Dir/var/lib/rpm/";
    $rpmListFile1 = tempnam('/tmp', 'ostree_');
    $cmd = "rpm -qa --dbpath=$path1 | sort > $rpmListFile1";
//     echo "<pre>CMD1=$cmd</pre>\n";
    exec($cmd);

    $path2 = $this->varsDir . "/$version2Dir/var/lib/rpm/";
    $rpmListFile2 = tempnam('/tmp', 'ostree_');
    $cmd = "rpm -qa --dbpath=$path2 | sort > $rpmListFile2";
//     echo "<pre>CMD2=$cmd</pre>\n";
    exec($cmd);

    $cmd = "comm -3 '--output-delimiter=|' $rpmListFile1 $rpmListFile2";
    $output = [];
//     echo "<pre>CMD3=$cmd</pre>\n";
    exec($cmd, $output);
//     echo "<pre>RPMCOMM=" . print_r($output, 1) . "</pre>\n";

    $rpms1= []; $rpms2 = [];
    foreach ($output as $diff) {
      $path = explode('|', $diff);
      if (count($path) == 1) {
        $fullName = $path[0];
        $shortName = repos::fullRPMNameToShort($fullName);
        $rpms1[$shortName] = $fullName;
      } else {
        $fullName = $path[1];
        $shortName = repos::fullRPMNameToShort($fullName);
        $rpms2[$shortName] = $fullName;
      }
    }
//     echo "<pre>RPMS1=". print_r($rpms1, 1) . "</pre>";
//     echo "<pre>RPMS2=". print_r($rpms2, 1) . "</pre>";
    $ret = ['new'=>[], 'changed'=>[], 'deleted'=>[]];
    foreach ($rpms1 as $short=>$full) {
      if (key_exists($short, $rpms2)) {
        $full2 = $rpms2[$short];
        $ret['changed'][$short] = [$full2, $full];
      } else {
        $ret['new'][$short] = $full;
      }
    }
    foreach ($rpms2 as $short=>$full) {
      if (!key_exists($short, $rpms1)) {
        $ret['deleted'][$short] = $full;
      }
    }
    return $ret;
  }

  static function cmpByDate($c1, $c2) {
  $ret = strcmp($c1['Date'], $c2['Date']);
  return $ret;
  }

  static function sizeToText($size) {
    if ($size > 2**30) {
      $ret = sprintf( "%.2f", $size/(2**30)).'GiB';
    } else {
      if ($size > 2**20) {
      $ret = sprintf( "%.2f", $size/(2**20)).'MiB';
      } else {
        if ($size > 2**10) {
          $ret = sprintf( "%.2f", $size/(2**10)).'KiB';
        } else {
          $ret = $size . "B";
        }
      }
    }
    return $ret;
  }


}
