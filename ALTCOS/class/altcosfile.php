 <?php
require_once "repo.php";
class altcosfile {

    function __construct($ref) {
      $file = $_SERVER['DOCUMENT_ROOT'] . "/ALTCOS/streams/" . repos::refToDir($ref) . "/ALTCOSfile";
//       $this->operators = [];
      if (!file_exists($file)) {
        $this->error = "Файл $file отсутствует";
        return;
      }
      $this->ref = $ref;
      $fp = fopen($file, 'r');
      $this->data = json_decode(fread($fp, filesize($file)), true);
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
