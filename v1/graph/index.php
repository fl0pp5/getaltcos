<?php
//phpinfo();
$basearch = @$_REQUEST['basearch'];
if (strlen($basearch) ==0 ) {
  errorReply(1, 'Parameter basearch is not defined');
}
$stream = @$_REQUEST['stream'];
if (strlen($stream) ==0 ) {
  errorReply(2, 'Parameter stream is not defined');
}


$output=[];
$repoType = substr($_SERVER['REMOTE_ADDR'], 0, 5) == '10.0.' ? 'bare' : 'archive';
$repo="/var/www/vhosts/getacos/ACOS/streams/acos/$basearch/$stream/$repoType/repo";
//echo "REPO=$repo<br>\n";
if (!file_exists("$repo/config")) {
  errorReply(3, "Stream $stream does not have reposutory");
}

$ref = "acos/$basearch/$stream";

$cmd = "ostree --repo=$repo log $ref";
#echo "CMD=$cmd<br>\n";
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

$index = 0;
foreach($commits as $id => $commit) {
  $commits[$id]['index'] = "" . $index;
  $index += 1;
}

#print_r($commits);

$nodes = [];
$edges = [];
foreach($commits as $id => $commit) {
  $node = [];
  $node['version'] = $commit['Version'];
  $node['metadata'] = [];
  $node['metadata']['org.fedoraproject.coreos.releases.age_index'] = $commit['index'];
  $node['metadata']['org.fedoraproject.coreos.scheme'] = 'checksum';
  $node['payload'] = $id;
  $nodes[] = $node;
  if (key_exists('Parent', $commit)) {
    $parent = $commit['Parent'];
    $parentIndex = (int)$commits[$parent]['index'];
    $index = (int)$commit['index'];
    $edges[] = [ $parentIndex, $index ];
#    $edges[] = [ $index, $parentIndex ];
 
  } 
}

$graph = [ 'nodes' => $nodes, 'edges' => $edges ];

$ret = json_encode($graph, JSON_PRETTY_PRINT);
header('Content-type: application/json');
echo $ret;


# print_r($graph);
function cmpByDate($c1, $c2) {
  $ret = strcmp($c1['Date'], $c2['Date']);
  return $ret;
}

function errorReply($num, $str) {
  http_response_code(400);
  header('Content-type: application/json');
  $ret['kind'] = $num;
  $ret['value'] = $str;
  echo json_encode($ret, JSON_PRETTY_PRINT);;
  exit($num);
}
