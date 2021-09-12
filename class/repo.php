<?php
require_once "repos.php";
class repo {
  function __construct($ref, $repoType='bare') {
    $this->repoType = $repoType;
    $this->refRepoDir = repos::refRepoDir($ref);
    $this->refDir = $_SERVER['DOCUMENT_ROOT'] . "/ACOS/streams/" . $this->refRepoDir;
    $this->repoDir = $this->refDir . "/$repoType/repo";
    $this->rootsDir = $this->refDir . "/roots";
    $this->varsDir = $this->refDir . "/vars";
    $this->commits = [];
    $this->lastCommit = [];
    $this->lastCommitId = [];
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
    $cmd = "ostree refs --repo=".$this->repoDir;
    $output = [];
//    echo "<pre>CMD=$cmd</pre>\n";
    exec($cmd, $output);
//    echo "<pre>REFS=" . print_r($output, 1) . "</pre>\n";
    return $output;
  }

  function deleteRef($ref) {
    $cmd = "sudo ostree refs --delete $ref --repo=".$this->repoDir .' 2>&1';;
    $output = [];
    echo "<pre>CMD=$cmd</pre>\n";
    exec($cmd, $output);
    echo "<pre>REFS=" . print_r($output, 1) . "</pre>\n";
    return $output;
  }

  function createRef($ref, $commitId) {
    $cmd = "sudo ostree refs --create=$ref $commitId --repo=".$this->repoDir . ' 2>&1';
    $output = [];
    echo "<pre>CMD=$cmd</pre>\n";
    exec($cmd, $output);
    echo "<pre>REFS=" . print_r($output, 1) . "</pre>\n";
    return $output;
  }

  function deleteCommit($commitId) {
    $cmd = "sudo ostree prune --delete-commit $commitId --repo=".$this->repoDir . ' 2>&1';;
    $output = [];
    echo "<pre>CMD=$cmd</pre>\n";
    exec($cmd, $output);
    echo "<pre>REFS=" . print_r($output, 1) . "</pre>\n";
    return $output;
  }

  function getCommits($ref) {
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
    $commitsIds = array_key($commits);
    $nCommits = count($commitsIds);
    if ($nCommits > 0) {
      $this->lastCommitId[$ref] = $nCommits - 1;
      $this->lastCommit[$ref] = $commits[$nCommits - 1];
    }
    return $commits;
  }

  function lastCommit($ref) {
    if (!key_exists($ref, $this->commits)) {
      $this->getCommits($ref);
    }
    return $this->lastCommit[$ref];
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

  static function subRef($ref, $subName) {
    $path = explode('/', $ref);
    $lastN = count($path) - 1;
    $path[$lastN] = ucfirst($path[$lastN]);
    $path[] = $subName;
    $ret = implode('/', $path);
    return $ret;
  }

  static function cmpByDate($c1, $c2) {
  $ret = strcmp($c1['Date'], $c2['Date']);
  return $ret;
}


}
