<?php
class repo {
  function __construct($ref, $typeRepo) {
    $this->path = $_SERVER['DOCUMENT_ROOT'] . "/ACOS/streams/$ref/$typeRepo/repo";
  }

}
