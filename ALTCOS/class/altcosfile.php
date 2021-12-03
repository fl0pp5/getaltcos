 <?php
require_once "repo.php";
require_once('vendor/autoload.php');
use Symfony\Component\Yaml\Yaml;
class altcosfile {

    function __construct($ref) {
      $this->path = altcosfile::getFilePath($ref);
      $this->file = $_SERVER['DOCUMENT_ROOT'] . $this->path;
//       $this->operators = [];
      $this->error = false;
      if (!file_exists($this->file)) {
        $this->error = "Файл " . $this->path ." отсутствует";
        return;
      }
      $this->ref = $ref;
      $this->filemtime = filemtime($this->file);
      list($data, $error) = altcosfile::loadALTCOSfile($this->file);
      if ($error) {
        $this->error = $error;
        return;
      }
      $this->data = $data;
    }

    static function getFilePath($ref) {
      $ret =  "/ALTCOS/streams/" . repos::refToDir($ref) . "/ALTCOSfile";
      return $ret;
    }

    static function loadALTCOSfile($file) {
      $error = false;
      try {
          $data = Yaml::parseFile($file);
      } catch (ParseException $exception) {
          $error = sprintf('Некорректный YAML-файл " . $this->file . ": %s', $exception->getMessage());
      }
      return [$data, $error];
    }

    function getButaneFile() {
      $ret = key_exists('butanefile', $this->data) ? $this->data['butanefile'] : 'butane.yml';
      return $ret;
    }

    function getRPMS() {
      $ret = is_array(@$this->data['rpms']) ? $this->data['rpms'] : [];
      return $ret;
    }

    function getPodmanImages() {
      $ret = is_array(@$this->data['podman']) && @is_array($this->data['podman']['images']) ? $this->data['podman']['images'] : [];
      return $ret;
    }


    /*
     * Формирует список поддиректориев содержащих ALTCOSfile's
     */
    static function getAcosSubRefs($ref) {
      $refDir = $_SERVER['DOCUMENT_ROOT'] . "/ALTCOS/streams/" . repos::refToDir($ref);
      $ret = [];
      $fd = dir($refDir);
      while ($entry = $fd->read()) {
        if (substr($entry, 0, 1) == '.' || in_array($entry, ['vars', 'roots'])) continue;
        $acosDir = "$refDir/$entry";
        $acosFile = "$acosDir/ALTCOSfile";
        if (file_exists($acosFile)) {
          $subRef = repos::dirToRef("$ref/$entry");
          $ret[] = $subRef;
        }
      }
      return $ret;
    }

}
