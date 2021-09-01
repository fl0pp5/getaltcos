<?php
require_once "repos.php";
class repo {
  function __construct($ref, $repoType='bare') {
    $this->repoDir = $_SERVER['DOCUMENT_ROOT'] . "/ACOS/streams/$ref/$repoType/repo";
  }

  function haveConfig() {
    $configFile = $this->repoDir."/config";
    $ret = file_exists($configFile);
//    echo "<pre>CONFIGFILE=$configFile<br>\n";
    return $ret;
  }

  function getRefs() {
    $cmd = "ostree refs --repo=".$this->repoDir;
    $output = [];
//    echo "<pre>CMD=$cmd</pre>\n";
    exec($cmd, $output);
//    echo "<pre>REFS=" . print_r($output, 1) . "</pre>\n";
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
    return $commits;
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
    //echo "CMD=$cmd<br>\n";
    exec($cmd, $output);
    //print_r($output);   
    return $output;
  }       
  
  static function cmpByDate($c1, $c2) {
  $ret = strcmp($c1['Date'], $c2['Date']);
  return $ret;
}


}
