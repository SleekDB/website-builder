<?php

require_once __DIR__ . "/vendor/autoload.php";
require_once __DIR__ . "/packages/Ingestor.php";

echo "Compilation started\n";

(new Ingestor('2.50'))->ingest();

echo "Compilation ended\n";
