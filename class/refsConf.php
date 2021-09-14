<?php
/*
 * Класс для работы с данными по веткам потоков
 */
class refsConf {

  function __construct($ref, $version) {
    $this->ref = $ref;
    $this->version = $version;
    $this->refRepoDir = repos::refRepoDir($ref);
    $this->refDir = $_SERVER['DOCUMENT_ROOT'] . "/ACOS/streams/" . $this->refRepoDir;
    $this->varsDir = $this->refDir . "/vars/" . repos::refVersionDatesSubDir($ref);
    $this->versionDir = $this->varsDir . '/' . repos::versionVarSubDir($version);
    $this->refConfFile = $this->versionDir . "/refConf.json";
    if (file_exists($this->refConfFile)) {
      $fp = fopen($this->refConfFile, 'r');
      $this->data = json_decode(fread($fp, filesize($this->refConfFile)), true);
      fclose($fp);
//     echo "<pre>THIS=". print_r($this, 1); echo "</pre><br>\n";
    } else {
      $this->data = ['ref' => $ref, 'version' => $version];
    }
  }

  function save() {
    $oldConf = $this->refConfFile . ".old";
    unlink($oldConf);
    rename($this->refConfFile, $oldConf);
    $fp = fopen($this->refConfFile, 'w');
    $data = json_encode($this->data, JSON_PRETTY_PRINT);
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
