<?php

require_once __DIR__ . "/Compiler.php";

class Ingestor
{
  private $docsDirectory;
  private $currentVersion;

  function __construct($currentVersion)
  {
    $this->docsDirectory = realpath(__DIR__ . "/../../docs/");
    $this->currentVersion = $currentVersion;
  }

  function getAvailableVersions()
  {
    return array_diff(
      scandir($this->docsDirectory, SCANDIR_SORT_ASCENDING),
      array('..', '.', '.git', 'versions.md', '.gitignore')
    );
  }

  function ingest()
  {
    foreach ($this->getAvailableVersions() as $key => $version) {
      (new Compiler($version))->setCurrentVersion($this->currentVersion)->compile();
    }
  }
}