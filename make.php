<?php

require_once __DIR__ . "/vendor/autoload.php";
require_once __DIR__ . "/packages/Ingestor.php";

echo "Compilation started\n";

(new Ingestor())->ingest();

echo "Compilation ended\n";
