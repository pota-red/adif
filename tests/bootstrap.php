<?php

declare(strict_types=1);

// Load Composer autoloader
require_once dirname(__DIR__) . '/vendor/autoload.php';

// Define test constants
define('ADIF_TEST_ROOT', dirname(__DIR__));
define('ADIF_TEST_DATA_DIR', ADIF_TEST_ROOT . '/testdata');

// Helper function for test data paths
function testDataPath(string $filename): string
{
    return ADIF_TEST_DATA_DIR . '/' . $filename;
}

// Ensure test data directory exists
if (! is_dir(ADIF_TEST_DATA_DIR)) {
    throw new RuntimeException('Test data directory not found: ' . ADIF_TEST_DATA_DIR);
}
