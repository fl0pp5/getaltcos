<?php
require_once "repo.php";
require_once('vendor/autoload.php');
use Symfony\Component\Yaml\Yaml;
class altcosfile {

  function __construct($ref) {
    $DOCUMENT_ROOT = $_SERVER['DOCUMENT_ROOT'];
    $this->BINDIR = "$DOCUMENT_ROOT/ostree/bin";
    $this->path = altcosfile::getFilePath($ref);
    $this->file = $_SERVER['DOCUMENT_ROOT'] . $this->path;
//       $this->operators = [];
    $this->error = false;
    if (!file_exists($this->file)) {
      $this->error = "ALTCOSfile файл " . $this->file ." отсутствует";
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
    $this->environments = [];
  }

  function notCorrect() {
    if (!key_exists('version', $this->data)) {
      return "Поле version отсутствует";
    }
    return false;
  }

  static function getFileDir($ref) {
    $ret =  "/ALTCOS/streams/" . repos::refToDir($ref);
    return $ret;
  }

  static function getFilePath($ref) {
    $ret = altcosfile::getFileDir($ref) . "/ALTCOSfile_v2.yml";
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

  function haveButaneFile() {
    $butanefile = $_SERVER['DOCUMENT_ROOT'] . altcosfile::getFileDir($this->ref) . "/BUTANEfile.yml";
//     echo "<pre>BUTANEFILE=$butanefile</pre>";
    $ret = file_exists($butanefile);
    return $ret;
  }

  function getActions() {
    $ret = is_array(@$this->data['actions']) ? $this->data['actions'] : [];
    return $ret;
  }

  function execActions($mergeDir, $subRef) {
    foreach ($this->getActions() as $subActions ) {
      foreach ($subActions as $actionName => $actionPars) {
        echo "<pre>actionName=$actionName\n</pre>";
        switch($actionName) {
          case 'rpms':
            $cmd = $this->BINDIR . "/apt-get_update.sh $subRef";
            echo "APT-GET_UPDATETCMD=$cmd\n";
            $output = [];
#            exec($cmd, $output);
            echo "APT-GET_UPDATE=<pre>" . implode("\n",$output). "</pre>";
            flush();
            $rpms = implode(' ', array_keys($actionPars));
            $cmd = $this->BINDIR . "/apt-get_install.sh '$subRef' $rpms";
            echo "APT-GET_INSTALL CMD=$cmd\n";
            $output = $this->runCmdWithEnv($cmd);
            echo "APT-GET_INSTALL OUTPUT=<pre>" . implode("\n",$output). "</pre>";
            flush();
            break;
          case 'env':
            foreach ($actionPars as $envName => $envValue) {
//               echo "<pre>ENV:".print_r($env, 1)."</pre>\n";
              $cmd = "$envName=\"$envValue\";echo \$$envName";
//               echo "<pre>ENV: $envName = $cmd</pre>\n";
              $output = [];
              exec($cmd, $output);
              $output = implode("\n", $output);
//               echo "<pre>OUTPUT=$output</pre>";
              $this->environments[$envName] = $output;
            }
            echo "<PRE>ENVIRONMENTS=".print_r($this->environments, 1)."</pre>\n";
            break;
          case 'podman':
            if (key_exists('images', $actionPars)) {
              $images = [];
              foreach ($actionPars['images'] as $image =>$imageVars) {
                $image .= ":" . $imageVars['version'];
                $images[] = $image;
              }
              $podmanImages = implode(' ', $images);
//             echo "<pre>IMAGES=$images</pre>\n";
              $cmd = $this->BINDIR . "/skopeo_copy.sh $mergeDir $podmanImages";
              echo "SKOPEO_COPY CMD=$cmd\n";
              $output = $this->runCmdWithEnv($cmd);
              echo "SKOPEO_COPY OUTPUT=<pre>" . implode("\n",$output). "</pre>";
              flush();
            }
            break;

          case 'copy':
            break;

          case 'run':
            break;
          default:
        }
      }
    }
  }

  function setEnvironments() {
    $ret = [];
    foreach ($this->environments as $name => $value) {
      $ret[] = "export $name=\"$value\"";
    }
//     $ret[] = "";
    $ret = implode("\n", $ret) . "\n";
    return $ret;
  }

  function runCmdWithEnv($cmd) {
    $environments = $this->setEnvironments();
    $envCmd = "${environments}${cmd}";
    echo "<pre>ENVCMD=$envCmd</pre>\n";
    $output = [];
//     exec($envCmd, $output);
    return $output;
  }

//   function getButaneFile() {
//     $ret = 'BUTANEfile.yml';
//     return $ret;
//   }
//
//   function getRPMS() {
//     $ret = is_array(@$this->data['rpms']) ? $this->data['rpms'] : [];
//     return $ret;
//   }
//
//   function getPodmanImages() {
//     $ret = is_array(@$this->data['podman']) && @is_array($this->data['podman']['images']) ? $this->data['podman']['images'] : [];
//     return $ret;
//   }


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
      $acosFile = "$acosDir/ALTCOSfile.yml";
      if (file_exists($acosFile)) {
        $subRef = repos::dirToRef("$ref/$entry");
        $ret[] = $subRef;
      }
    }
    return $ret;
  }

}
