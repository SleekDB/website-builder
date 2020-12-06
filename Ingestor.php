<?php

require_once __DIR__ . "/Compiler.php";

class Ingestor
{

  function __construct()
  {
    $this->docsDirectory = realpath(__DIR__ . "/../docs/");
  }

  function getAvailableVersions()
  {
    return array_diff(
      scandir($this->docsDirectory, SCANDIR_SORT_ASCENDING),
      array('..', '.', '.git')
    );
  }

  function ingest()
  {
    foreach ($this->getAvailableVersions() as $key => $version) {
      (new Compiler($version))->compile();
    }
  }
}
