<?php

function listStreams() {
  $fd = opendir($_SERVER['DOCUMENT_ROOT'] . "/ACOS/streams/acos/x86_64");
  $ret = [];
  while ($entry=readdir($fd)) {
    if (substr($entry,0,1) == '.') continue;
    $ret[] = $entry;
  } 
  return $ret;
}


function getRefs($stream, $typeRepo='bare') {
  $repoDir = $_SERVER['DOCUMENT_ROOT'] . "/ACOS/streams/acos/x86_64/$stream/$typeRepo/repo";
  $cmd = "ostree refs --repo=$repoDir";
  $output = [];
  //echo "<pre>CMD=$cmd</pre>\n";
  exec($cmd, $output);
  //echo "<pre>REFS=" . print_r($output, 1) . "</pre>\n";
  return $output; 
}


function getCommits($repo, $ref) {
  $cmd = "ostree --repo=$repo log $ref";
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
  uasort($commits, 'cmpByDate');
  return $commits;
}

