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

  /**
   * Возвращает тропу, где находятся репозитории bare, archive
   * acos/x86_64/sisyphus -> acos/x86_64/sisyphus
   * acos/x86_64/Sisyphus/apache -> acos/x86_64/sisyphus
   */
  static function refDir($ref) {
    $path = array_slice(explode('/', $ref), 0, 3);
    $path[2] = strtolower($path[2]);
    $ret = implode('/', $path);
    return $ret;
  }

}
