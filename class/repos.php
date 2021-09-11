<?php
class repos {

  static $OSs = ['acos' => 'ALTLinux Container OS'];

  static function listOSs() {
    $ret = array_keys(repos::OSs);
    return $ret;
  }

  static function getOSName($os) {
    $ret = repos::$OSs[$os];
    return $ret;
  }

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
  static function refRepoDir($ref) {
    $path = array_slice(explode('/', $ref), 0, 3);
    $path[2] = strtolower($path[2]);
    $ret = implode('/', $path);
    return $ret;
  }

  /*
   * Возвращает вариант ветки
   * acos/x86_64/Sisyphus/apache -> sisyphus_apache.$date.$major.$minor
   */
  static function refVariant($ref, $date=false, $major=0, $minor=0) {
    if (!$date) {
      $date = strftime("%Y%m%d");
    }
    $path = explode('/', $ref);
    $path[2] = strtolower($path[2]);
    $stream = implode('_', array_slice($path, 2));
    $ret = "$stream.$date.$major.$minor";
    return $ret;
  }

  /*
   *
   */
  static function variantVarSubDir($variant) {
    $path = explode('.', strtolower($variant));
    $stream = $path[0];
    $date = $path[1];
    $major = $path[2];
    $minor = $path[3];
    $ret = "$stream/$date/$major/$minor";
    return $ret;
  }


}
