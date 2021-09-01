<?php
class repos {

  static function listArchs() {
    $fd = opendir($_SERVER['DOCUMENT_ROOT'] . "/ACOS/streams/acos/");
    $ret = [];
    while ($entry=readdir($fd)) {
      if (substr($entry,0,1) == '.') continue;
      $ret[] = $entry;
    }
    return $ret;
  }

  static function listStreams($arch='x86_64') {
    $fd = opendir($_SERVER['DOCUMENT_ROOT'] . "/ACOS/streams/acos/$arch");
    $ret = [];
    while ($entry=readdir($fd)) {
      if (substr($entry,0,1) == '.') continue;
      $ret[] = $entry;
    }
    return $ret;
  }

  static function repoTypes() {
    $ret = ['bare', 'archive'];
    return $ret;
  }

}
