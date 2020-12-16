<?php

class Compiler
{

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

  function setCurrentVersion($currentVersion)
  {
    $this->currentVersion = trim($currentVersion);
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
    return @json_decode($matches[1], true);
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

  function getRenderedPagesAndMenuItems()
  {
    $menus = [];
    $pages = [];
    foreach ($this->getMarkdownFiles() as $file) {
      $fileData = file_get_contents($this->markdownDocumentsDirectory . $file);
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
        "html" => (new Parsedown())->text($this->removeMetaData($fileData)),
        "metadata" => $metadata,
        "fileName" => $file
      ];
      echo "✅ $file (" . $this->version . ") \n";
    }
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
        
        <div class="container">
          <div class="header">
            ' . $this->getNavbar() . '
          </div>
          <div class="app">
            <div class="left">
              ' . $this->getSidebar() . '
            </div>
            <div class="right">
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
      system("rm -R " . $this->dist);
    }
  }

  function compile()
  {
    $this->removeDist();
    $this->getRenderedPagesAndMenuItems();
    $siteHtml = $this->getRenderedSite();
    mkdir($this->dist);
    file_put_contents($this->dist . "index.html", $siteHtml);
    copy($this->currentVersionDist . "/index.html", $this->websitePath . "/index.html");
  }
}
