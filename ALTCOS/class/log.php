<?php

class log {
  function __construct($module) {
    $this->module = $module;
    $logDir = "/var/log/altcos/";
    if (!is_dir($logDir)) {
      exec("sudo mkdir -p -m 0777 $logDir");
//       mkdir($logDir, 0755, true);
    }
    $logFile = "$logDir/$module.log";
    $this->fp = fopen($logFile, "a");
//     echo "LOGFILE=$logFile THIS=" . print_r($this, 1);
    $this->write("START module $module REMOTE_ADDR" . $_SERVER['REMOTE_ADDR'] ."QUERY_STRING=" . $_SERVER['QUERY_STRING']);
  }

  function __destruct() {
    $this->write("STOP module " . $this->module . " REMOTE_ADDR" . $_SERVER['REMOTE_ADDR']);
    fclose($this->fp);
  }

  function write($str) {
    $datetime = date("Y-m-d H:i:s");
    fwrite($this->fp, "$datetime: " . $str);
  }
}
