<?php

require_once __DIR__ . "/Compiler.php";

class Ingestor
{
  private $docsDirectory;
  private $currentVersion;
  private $bugFixOnlyVersion;
  private $archivedVersions;

  function __construct()
  {
    $this->docsDirectory = realpath(__DIR__ . "/../../docs/");
    $this->getVersions();
  }

  function getVersions()
  {
    $versions = $this->getAvailableVersions();

    // Filter only directories
    $versions = array_filter($versions, function ($item) {
      return is_dir($this->docsDirectory . '/' . $item);
    });

    // Sort versions using version_compare in descending order
    usort($versions, function ($a, $b) {
      return version_compare($b, $a);
    });

    $versions = array_values($versions); // Re-index array

    $this->currentVersion = isset($versions[0]) ? $versions[0] : '';
    $this->bugFixOnlyVersion = isset($versions[1]) ? $versions[1] : '';
    $this->archivedVersions = array_slice($versions, 2);
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
      (new Compiler($version))->setCurrentVersion(
        $this->currentVersion,
        $this->bugFixOnlyVersion,
        $this->archivedVersions
      )
        ->compile();
    }
  }
}
