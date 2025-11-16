<?php

class Compiler
{
  private $rootPath;
  private $title;
  private $description;
  private $menus;
  private $pages;
  private $version;
  private $currentVersion;
  private $docsDir;
  private $markdownDocumentsDirectory;
  private $websitePath;
  private $distRoot;
  private $dist;
  private $currentVersionDist;
  private $bugFixOnlyVersion;
  private $archivedVersions;

  function __construct($version)
  {
    $this->rootPath = __DIR__;

    $this->title = "";
    $this->description = "";

    $this->menus = [];
    $this->pages = [];

    $this->version = $version;
    $this->currentVersion = "";

    $this->docsDir = realpath($this->rootPath . "/../../docs/" . "/") . "/";
    $this->markdownDocumentsDirectory = $this->docsDir . $this->version . "/";

    $this->websitePath = realpath($this->rootPath . "/../../sleekdb.github.io/") . "/";
    $this->distRoot = $this->websitePath . "versions/";
    $this->dist = $this->distRoot . $this->version . "/";

    $this->currentVersionDist = null;
  }

  function getTemplate($file)
  {
    return file_get_contents($this->rootPath . "/../" . "templates/" . $file);
  }

  function getHeadTag()
  {
    $headTagHtml = $this->getTemplate("head.tag.html");
    $headTagHtml = str_replace("{{--title--}}", $this->title, $headTagHtml);
    $headTagHtml = str_replace("{{--description--}}", $this->description, $headTagHtml);
    return $headTagHtml;
  }

  function getNavbar()
  {
    return str_replace("{{--version--}}", $this->version, $this->getTemplate("navbar.html"));
  }

  function getSidebar()
  {
    $menus = "";
    foreach ($this->menus as $menu) {
      if (!$this->isCurrentVersion() && $menu['url'] === "features") {
        // Skip adding the feature page to the sidebar for non-current versions
        continue;
      }

      if (isset($menu['title'])) {
        $menus .= '
          <a class="gotoblock" href="#/' . $menu['url'] . '">
            <ion-icon name="' . $menu['icon'] . '"></ion-icon> 
            ' . $menu['title'] . '
        </a>';
      }
    }
    $sidebarHtml = $this->getTemplate("sidebar.html");
    return str_replace("{{--sidebar--}}", $menus, $sidebarHtml);
  }

  function getContent()
  {
    $dom = "";
    foreach ($this->pages as $key => $page) {
      $dom .= '
          <div class="intro" id="block_' . $page["metadata"]["url"] . '">
            ' . $page["html"] . '
            <div class="editlink" 
              file="' . $page["fileName"] . '" 
              version="' . $this->version . '">
            </div>
          </div>
        ';
    }
    return $dom;
  }

  function setTitle($title)
  {
    $this->title = $title;
    return $this;
  }

  function setDescription($description)
  {
    $this->description = $description;
    return $this;
  }

  function setCurrentVersion($currentVersion, $bugFixOnlyVersion, $archivedVersions)
  {
    $this->currentVersion = trim($currentVersion);
    $this->bugFixOnlyVersion = trim($bugFixOnlyVersion);
    $this->archivedVersions = $archivedVersions;
    $this->currentVersionDist = $this->distRoot . $this->currentVersion . "/";
    return $this;
  }

  function getMetaDataPattern()
  {
    return "/<!--METADATA(.*)!METADATA-->/s";
  }

  function getMetaData($page)
  {
    preg_match($this->getMetaDataPattern(), $page, $matches);
    if (isset($matches[1])) {
      return json_decode($matches[1], true);
    }
    return null;
  }

  function removeMetaData($data)
  {
    return preg_replace($this->getMetaDataPattern(), '', $data, 1);
  }

  function getMarkdownFiles()
  {
    $listFile = $this->markdownDocumentsDirectory . "lists.json";
    if (file_exists($listFile)) {
      $files = file_get_contents($listFile);
      $files = json_decode($files, true);
      return $files;
    }
    return [];
  }

  function addVersionsFile(&$menus, &$pages, $parseDownExtra)
  {
    $fileName = "versions.md";

    if (file_exists($this->docsDir . $fileName)) {
      $fileData = file_get_contents($this->docsDir . $fileName);

      $fileData .= "\n\n";
      $fileData .= "# Latest Version\n";
      $fileData .= "- **[" . $this->currentVersion . "](/)**  (Actively Maintained Version)\n\n";
      $fileData .= "Always refer to the latest version for the most up-to-date features and security updates.\n\n";

      if ($this->bugFixOnlyVersion) {
        $fileData .= "## Bug Fix Only Version\n";
        $fileData .= "- **[" . $this->bugFixOnlyVersion . "](/versions/" . $this->bugFixOnlyVersion . "/)** (Bug Fix Only)\n\n";
      }

      if (count($this->archivedVersions) > 0) {
        $fileData .= "## Archived Versions\n";
        foreach ($this->archivedVersions as $archivedVersion) {
          $fileData .= "- **[" . $archivedVersion . "](/versions/" . $archivedVersion . "/)** (Archived)\n";
        }
        $fileData .= "\n";
      }

      $metadata = $this->getMetaData($fileData);

      if ($metadata !== false) {
        if (!isset($metadata['url']) || !$metadata['url']) {
          $metadata['url'] = "/";
        }
        if (isset($metadata['website_title'])) {
          $this->title = $metadata['website_title'];
        }
        if (isset($metadata['website_description'])) {
          $this->description = $metadata['website_description'];
        }
        $menus[] = $metadata;
      }

      $pages[] = [
        "html" => $parseDownExtra->text($this->removeMetaData($fileData)),
        "metadata" => $metadata,
        "fileName" => $fileName
      ];

      echo "✅ $fileName (" . $this->version . ") \n";
    } else {
      echo "🛑 $fileName not found (" . $this->version . ") \n";
    }
  }

  function addPageFile(&$menus, &$pages, $parseDownExtra, $fileName)
  {
    if (file_exists($this->docsDir . $fileName)) {
      $fileData = file_get_contents($this->docsDir . $fileName);
      $metadata = $this->getMetaData($fileData);
      if ($metadata !== false) {
        if (!isset($metadata['url']) || !$metadata['url']) {
          $metadata['url'] = "/";
        }
        if (isset($metadata['website_title'])) {
          $this->title = $metadata['website_title'];
        }
        if (isset($metadata['website_description'])) {
          $this->description = $metadata['website_description'];
        }
        $menus[] = $metadata;
      }
      $pages[] = [
        "html" => $parseDownExtra->text($this->removeMetaData($fileData)),
        "metadata" => $metadata,
        "fileName" => $fileName
      ];
      echo "✅ $fileName (" . $this->version . ") \n";
    } else {
      echo "🛑 $fileName not found (" . $this->version . ") \n";
    }
  }

  function addHomePageFile(&$menus, &$pages, $parseDownExtra)
  {
    $fileData = "";
    $file = $this->isCurrentVersion() ?  "home-page.md" : "home-page-old-versions.md";
    $fileData = file_get_contents($this->docsDir . $file);

    $metadata = $this->getMetaData($fileData);

    if ($this->isCurrentVersion()) {
      $this->title = $metadata['website_title'];
      $this->description = $metadata['website_description'];
    } else {
      $this->title = "SleekDB " . $this->version;
      $this->description = "This is the documentation for SleekDB Version: " . $this->version;

      $fileData .= "\n# SleekDB: " . $this->version . "\n\n" .
        "This is the documentation for SleekDB version " . $this->version . ".\n\n" .
        "For the latest features and updates, please visit the [current version](/).\n\n</br></br>\n" .
        "# **IMPORTANT:**\n" .
        "## This version (" . $this->version . ") is no longer actively maintained.\n" .
        "### Please refer to the current version (" . $this->currentVersion . ") for the latest updates.\n\n" .
        "---\n\n";
      $metadata = $this->getMetaData($fileData);
    }


    if ($metadata !== false) {
      if (!isset($metadata['url']) || !$metadata['url']) {
        $metadata['url'] = "/";
      }
      $menus[] = $metadata;
    }

    $pages[] = [
      "html" => $parseDownExtra->text($this->removeMetaData($fileData)),
      "metadata" => $metadata,
      "fileName" => $file
    ];

    echo "✅ $file (" . $this->version . ") \n";
  }

  function isCurrentVersion()
  {
    return $this->version === $this->currentVersion;
  }

  function getRenderedPagesAndMenuItems()
  {
    $parseDownExtra = new ParsedownExtra();

    $menus = [];
    $pages = [];

    $this->addHomePageFile($menus, $pages, $parseDownExtra);

    foreach ($this->getMarkdownFiles() as $file) {
      if (!$this->isCurrentVersion() && $file === "features.md") {
        // Skip processing the feature page for non-current versions
        continue;
      }

      if ($file === "contact.md") {
        // Skip processing the contact page
        continue;
      }

      if ($file === "home.md") {
        // The main home page is handled separately
        continue;
      }

      if ($file === "contributing.md") {
        // The contributing page is handled separately
        continue;
      }

      if (file_exists($this->markdownDocumentsDirectory . $file)) {
        $fileData = file_get_contents($this->markdownDocumentsDirectory . $file);
        $metadata = $this->getMetaData($fileData);

        // Replace install command with the appropriate version.
        if ($file === "installation.md" && !$this->isCurrentVersion()) {
          $fileData = str_replace(
            "composer require rakibtg/sleekdb",
            "composer require rakibtg/sleekdb ^" . $this->version,
            $fileData
          );
        }

        if ($metadata !== false) {
          if (!isset($metadata['url']) || !$metadata['url']) {
            $metadata['url'] = "/";
          }

          if (isset($metadata['website_title'])) {
            $this->title = $metadata['website_title'];
          }

          if (isset($metadata['website_description'])) {
            $this->description = $metadata['website_description'];
          }
          $menus[] = $metadata;
        }

        $pages[] = [
          "html" => $parseDownExtra->text($this->removeMetaData($fileData)),
          "metadata" => $metadata,
          "fileName" => $file
        ];

        echo "✅ $file (" . $this->version . ") \n";
      } else {
        echo "🛑 $file not found (" . $this->version . ") \n";
      }
    }

    $this->addPageFile($menus, $pages, $parseDownExtra, "support.md");
    $this->addPageFile($menus, $pages, $parseDownExtra, "contact.md");
    $this->addVersionsFile($menus, $pages, $parseDownExtra);

    $this->menus = $menus;
    $this->pages = $pages;
  }

  function getRenderedSite()
  {
    return '
        <!DOCTYPE html>
        <html lang="en">
          ' . $this->getHeadTag() . '
        <body>

        ' . $this->getTemplate("analytics.html") . '
        
        <div class="container">
          <div class="header">
            ' . $this->getNavbar() . '
          </div>
          <div class="app">
            <div class="left">
              ' . $this->getSidebar() . '
            </div>
            <div class="right" id="layoutRight">
              ' . $this->getContent() . '
            </div>
          </div>
        </div>
        
        <script src="/assets/app.js"></script>
        </body>
        </html>
      ';
  }

  function removeDist()
  {
    if (file_exists($this->dist)) {
      // Use escapeshellarg to prevent command injection
      system("rm -R " . escapeshellarg($this->dist));
    }
  }

  function compile()
  {
    $this->removeDist();
    $this->getRenderedPagesAndMenuItems();
    $siteHtml = $this->getRenderedSite();

    // Create directory with error handling
    if (!is_dir($this->dist)) {
      if (!mkdir($this->dist, 0755, true)) {
        throw new Exception("Failed to create directory: " . $this->dist);
      }
    }

    // Write file with error handling
    if (file_put_contents($this->dist . "index.html", $siteHtml) === false) {
      throw new Exception("Failed to write file: " . $this->dist . "index.html");
    }

    // Copy file with null check and error handling
    if ($this->currentVersionDist !== null) {
      $sourceFile = $this->currentVersionDist . "/index.html";
      $destFile = $this->websitePath . "/index.html";
      if (file_exists($sourceFile)) {
        if (!copy($sourceFile, $destFile)) {
          throw new Exception("Failed to copy file from $sourceFile to $destFile");
        }
      } else {
        echo "⚠️  Warning: Source file does not exist: $sourceFile\n";
      }
    }
  }
}
