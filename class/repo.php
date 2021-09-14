<?php
require_once "repos.php";
class repo {
  function __construct($ref, $repoType='bare') {
    $this->repoType = $repoType;
    $this->ref = $ref;
    $this->refs = false;
    $this->refRepoDir = repos::refRepoDir($ref);
    $this->refDir = $_SERVER['DOCUMENT_ROOT'] . "/ACOS/streams/" . $this->refRepoDir;
    $this->repoDir = $this->refDir . "/$repoType/repo";
    $this->rootsDir = $this->refDir . "/roots";
    $this->varsDir = $this->refDir . "/vars/" . repos::refVersionDatesSubDir($ref);
    $this->commits = false;
    $this->lastCommit = false;
    $this->lastCommitId = false;
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
    echo "<pre>CMD=$cmd</pre>\n";
    exec($cmd, $output);
    echo "<pre>MKDIR=" . print_r($output, 1) . "</pre>\n";
    $cmd = "sudo ostree init --mode=" . $this->repoType . " --repo=" .  $this->repoDir .' 2>&1';;
    $output = [];
    echo "<pre>CMD=$cmd</pre>\n";
    exec($cmd, $output);
    echo "<pre>INIT=" . print_r($output, 1) . "</pre>\n";


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
    echo "CMD=$cmd<br>\n";
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
    $cmd = "sudo ostree fsck $commitId  --repo=". $this->repoDir;
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
        }
      }
    }
  }

  static function cmpByDate($c1, $c2) {
  $ret = strcmp($c1['Date'], $c2['Date']);
  return $ret;
}


}
