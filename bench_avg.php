<?php

require_once 'vendor/autoload.php';

use Pota\Adif\Adif;
use Pota\Adif\Document;

if ($argc < 2) {
    echo "Usage: php bench_avg.php <adif_file> [iterations]\n";
    exit(1);
}

$file = $argv[1];
$iterations = isset($argv[2]) ? (int) $argv[2] : 10;

if (! file_exists($file)) {
    echo "File not found: $file\n";
    exit(1);
}

echo "Processing: $file\n";
echo 'File size: ' . number_format(filesize($file)) . " bytes\n";
echo "Running $iterations iterations...\n\n";

$all_timers = [];

for ($iter = 0; $iter < $iterations; $iter++) {
    $doc = new Document($file);
    $doc->setMode(Document::MODE_POTA);

    $doc->parse();
    $doc->sanitize();
    $doc->validate();
    $doc->dedupe();
    $doc->morph(Adif::MORPH_POTA_REFS);

    $timers = $doc->getTimers();
    foreach ($timers as $name => $ms) {
        if (! isset($all_timers[$name])) {
            $all_timers[$name] = [];
        }
        $all_timers[$name][] = $ms;
    }
}

echo "--- Average Timers (over $iterations runs) ---\n";
foreach ($all_timers as $name => $values) {
    $avg = array_sum($values) / count($values);
    $min = min($values);
    $max = max($values);
    printf("%12s: avg=%8.3f ms  min=%8.3f ms  max=%8.3f ms\n", $name, $avg, $min, $max);
}
