<?php
//phpinfo();

$rootdir = $_SERVER['DOCUMENT_ROOT'];
ini_set('include_path', "$rootdir/class");
require_once('repo.php');

$basearch = @$_REQUEST['basearch'];
if (strlen($basearch) ==0 ) {
  errorReply(1, 'Parameter basearch is not defined');
}
$stream = @$_REQUEST['stream'];
if (strlen($stream) ==0 ) {
  errorReply(2, 'Parameter stream is not defined');
}

if (key_exists('repoType', $_REQUEST)) {
  $repoType = $_REQUEST['repoType'];
} else {
  $repoType = substr($_SERVER['REMOTE_ADDR'], 0, 5) == '10.0.' ? 'bare' : 'archive';
}  
$repo = new repo("acos/$basearch/$stream", $repoType);

$output=[];
//echo "REPO=$repo<br>\n";
if (!$repo->haveConfig()) {
  errorReply(3, "Stream $stream does not have reposutory");
}

$ref = "acos/$basearch/$stream";
$commits = $repo->getCommits($ref);
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

function errorReply($num, $str) {
  http_response_code(400);
  header('Content-type: application/json');
  $ret['kind'] = $num;
  $ret['value'] = $str;
  echo json_encode($ret, JSON_PRETTY_PRINT);;
  exit($num);
}
