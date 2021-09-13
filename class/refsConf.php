<?php
/*
 * Класс для ведения отформации по веткам потоков
 */
class refsConf {

  function __construct($ref) {
    $this->ref = $ref;
    $this->refRepoDir = repos::refRepoDir($ref);
    $this->refDir = $_SERVER['DOCUMENT_ROOT'] . "/ACOS/streams/" . $this->refRepoDir;
    $this->repoDir = $this->refDir . "/$repoType/repo";
    $this->refsConfFile = $this->refDir . "/refsConf.json";
    if (file_exists($this->refsConfFile)) {
      $fp = fopen($this->refsConfFile, 'r');
      $this->refsData = json_decode(fread($fp, filesize($this->refsConfFile)), true);
      fclose($fp);
      $this->data = key_exists($ref, $this->refsData) ? $this->refsData[$ref] : ['ref' => $ref];
//     echo "<pre>THIS=". print_r($this, 1); echo "</pre><br>\n";
    } else {
      $this->refsData = [];
      $this->data = ['ref' => $ref];
    }
  }

  function save() {
    $oldConf = $this->refsConfFile . ".old";
    unlink($oldConf);
    rename($this->refsConfFile, $oldConf);
    $fp = fopen($this->refsConfFile, 'w');
    $this->refsData[$this->ref] = $this->data;
    $data = json_encode($this->refsData, JSON_PRETTY_PRINT);
//     echo "<pre>DATA=$data</pre><br>\n";
    fwrite($fp, $data);
//     echo "<pre>THIS=". print_r($this, 1); echo "</pre><br>\n";
    fclose($fp);
  }

  function getRefData() {
    $ret = $this->data;
    return $ret;
  }

  function addRpmList($rpmList) {
    sort($rpmList);
    $this->data['rpmListFullNames'] = $rpmList;
    $this->setRpmListShortNames($rpmList);
  }

  function getRpmShortName($rpmFullName) {
    $parts = explode('-', $rpmFullName);
    $rpmShortName = array_slice($parts, 0, count($parts) - 2);
    return implode('-', $rpmShortName);
  }

  function setRpmListShortNames($rpmList=false) {
    if (!$rpmList) $rpmList = $this->data['rpmListFullNames'];
    $this->data['rpmListShortNames'] = [];
    foreach ($rpmList as $rpmFullName) {
      $this->data['rpmListShortNames'][] = $this->getRpmShortName($rpmFullName);
    }
  }
}
