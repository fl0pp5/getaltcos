<?php
require_once "repo.php";
require_once('vendor/autoload.php');
use Symfony\Component\Yaml\Yaml;
class butanefile {

  function __construct($ref) {
    $this->path = butanefile::getFilePath($ref);
    $this->file = $_SERVER['DOCUMENT_ROOT'] . $this->path;
//       $this->operators = [];
    $this->error = false;
    if (!file_exists($this->file)) {
      $this->error = "butane-файл " . $this->path ." отсутствует";
      return;
    }
    $this->ref = $ref;
    $this->filemtime = filemtime($this->file);
    list($data, $error) = butanefile::loadBUTANEfile($this->file);
    if ($error) {
      $this->error = $error;
      return;
    }
    $this->data = $data;
  }

  function notCorrect() {
    $ret = [];
    if (!key_exists('version', $this->data)) {
      $ret[] = "Поле version отсутсвует";
    }
    if (!key_exists('variant', $this->data)) {
      $ret[] = "Поле version отсутсвует";
    }
    if (count($ret) > 0 ) {
      $ret = implode(', ', $ret);
    } else {
      $ret = false;
    }
    return $ret;
  }

  static function getFilePath($ref) {
    $ret =  "/ALTCOS/streams/" . repos::refToDir($ref) . "/BUTANEfile.yml";
    return $ret;
  }

  static function loadBUTANEfile($file) {
    $error = false;
    try {
        $data = Yaml::parseFile($file);
    } catch (ParseException $exception) {
        $error = sprintf('Некорректный YAML-файл " . $this->file . ": %s', $exception->getMessage());
    }
    return [$data, $error];
  }
}
