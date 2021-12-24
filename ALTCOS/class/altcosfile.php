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
    $ret = altcosfile::getFileDir($ref) . "/ALTCOSfile.yml";
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
    $BINDIR = $_SERVER['DOCUMENT_ROOT'] . "/ostree/bin";
    $this->environments['ROOTDIR'] = $mergeDir;
    foreach ($this->getActions() as $subActions ) {
      foreach ($subActions as $actionName => $actionPars) {
        echo "<pre>actionName=$actionName\n</pre>";
        switch($actionName) {
          case 'rpms':
            $cmd = $this->BINDIR . "/apt-get_update.sh $subRef";
            echo "APT-GET_UPDATETCMD=$cmd\n";
            $output = [];
            exec("stdbuf -oL $cmd", $output);
            echo "APT-GET_UPDATE=<pre>" . implode("\n",$output). "</pre>";
            flush();
            $rpms = implode(' ', array_keys($actionPars));
            $cmd = $this->BINDIR . "/apt-get_install.sh '$subRef' $rpms";
            echo "APT-GET_INSTALL CMD=$cmd\n";
            exec("stdbuf -oL $cmd", $output);
//             $output = $this->runCmdWithEnv($cmd);
            echo "<pre>APT-GET_INSTALL OUTPUT=<pre>" . implode("\n",$output). "</pre>";
            flush();
            break;
          case 'env':
            foreach ($actionPars as $envName => $envValue) {
              $cmd = $this->setEnvironments();
              if (is_array($envValue)) {
                if (key_exists('cmd', $envValue)) {
                  $envcmd =  $envValue['cmd'];
                  $cmd .= "sudo chroot $mergeDir sh -c \"$envcmd\"";
                }
              } else {
                $cmd .= "$envName=\"$envValue\";echo \$$envName";
              }
              echo "<pre>ENV: $envName = $cmd</pre>\n";
              $output = [];
              exec($cmd, $output);
              $output = str_replace("\n", ' ', implode("\n", $output));
              echo "<pre>OUTPUT=$output</pre>";
              $this->environments[$envName] = $output;
            }
            echo "<PRE>ENVIRONMENTS=".print_r($this->environments, 1)."</pre>\n";
            break;
          case 'podman':
            $images = [];
//             echo "<pre>PODMAN: ACTIONPARS=" . print_r($actionPars, 1) . "</pre>";
            if (key_exists('images', $actionPars) || key_exists('envListImages', $actionPars)) {
              if (key_exists('images', $actionPars)) {
                $images = array_merge($images, $actionPars['images']);
              }
//               echo "<pre>PODMAN: IMAGES=" . print_r($images, 1) . "</pre>";
              $podmanImages = implode(' ', $images);
//               echo "<pre>PODMANIMAGES=$podmanImages</pre>";
              if (key_exists('envListImages', $actionPars)) {
                foreach (explode(',', $actionPars['envListImages']) as $envName) {
                  $podmanImages .=  ' ' . $this->environments[$envName];
                }
              }
              $cmd = $this->BINDIR . "/skopeo_copy.sh $mergeDir $podmanImages";
              echo "SKOPEO_COPY CMD=$cmd\n";
//               exit();
              $output = $this->runCmdWithEnv($cmd);
              echo "SKOPEO_COPY OUTPUT=<pre>" . implode("\n",$output). "</pre>";
              flush();
            }
            break;

          case 'butane':
            echo "<pre>PODMAN: ACTIONPARS=" . print_r($actionPars, 1) . "</pre>";
            $refDir = repos::refToAbsDir($subRef);
            $yml = Yaml::dump($actionPars, 16384);
            echo "<pre>BUTANE: YML = $yml</yml>";
            $cmd = "echo \"$yml\" | $BINDIR/ignition.sh '$refDir' '$mergeDir'";
            $output = $this->runCmdWithEnv($cmd);
            echo "<pre>BUTANE OUTPUT=<pre>" . implode("\n",$output). "</pre>";
//             exit();
            break;

          case 'run':
            $cmd = "sudo chroot $mergeDir bash -c \"" . implode("\n",$actionPars) . '"';
            $output = $this->runCmdWithEnv($cmd);
            echo "<pre>RUN OUTPUT=<pre>" . implode("\n",$output). "</pre>";
//             exit();
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
    $ret = implode(";", $ret);
    if (trim($ret)) {
      $ret .= ';';
    }
    return $ret;
  }

  function runCmdWithEnv($cmd) {
    $environments = $this->setEnvironments();
    $cmd = "$cmd";
    $envCmd = "${environments}${cmd}";
    echo "<pre>ENVCMD=$envCmd</pre>\n";
    flush();
    $output = [];
    exec($envCmd, $output);
//     system($cmd);
    return $output;
  }

  function getButaneFile() {
    $ret = 'BUTANEfile.yml';
    return $ret;
  }
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
