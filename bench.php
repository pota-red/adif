<?php

require_once 'vendor/autoload.php';

use Pota\Adif\Adif;
use Pota\Adif\Document;

if ($argc < 2) {
    echo "Usage: php bench.php <adif_file>\n";
    exit(1);
}

$file = $argv[1];
if (! file_exists($file)) {
    echo "File not found: $file\n";
    exit(1);
}

echo "Processing: $file\n";
echo 'File size: ' . number_format(filesize($file)) . " bytes\n\n";

$doc = new Document($file);
$doc->setMode(Document::MODE_POTA);

$doc->parse();
echo 'Parsed entries: ' . $doc->count . "\n";

$doc->sanitize();
$doc->validate();
$doc->dedupe();
$doc->morph(Adif::MORPH_POTA_REFS);

echo "\n--- Timers ---\n";
$timers = $doc->getTimers();
foreach ($timers as $name => $ms) {
    printf("%12s: %10.3f ms\n", $name, $ms);
}

echo "\nResult: " . count($doc->getEntries()) . ' entries, ' .
     count($doc->getDupes()) . ' duplicates, ' .
     count($doc->getErrors()) . " errors\n";
