<?php

declare(strict_types=1);

namespace Pota\Adif\Tests\Performance;

use PHPUnit\Framework\TestCase;
use Pota\Adif\Adif;

final class PerformanceTest extends TestCase
{
    // Dedupe performance tests - validates O(n) optimization
    public function test_dedupe_performance_small_file(): void
    {
        $adif = new Adif;
        $adif->loadFile(testDataPath('small.adi'));
        $adif->parse();
        $doc = $adif->merge();

        $start = microtime(true);
        $doc->dedupe();
        $duration = (microtime(true) - $start) * 1000; // ms

        // Small file should be very fast (< 500ms)
        $this->assertLessThan(500, $duration, "Small file dedupe too slow: {$duration}ms");
    }

    public function test_dedupe_performance_medium_file(): void
    {
        $adif = new Adif;
        $adif->loadFile(testDataPath('medium.adi'));
        $adif->parse();
        $doc = $adif->merge();

        $start = microtime(true);
        $doc->dedupe();
        $duration = (microtime(true) - $start) * 1000; // ms

        // Medium file (1.4MB) should complete in < 2s
        $this->assertLessThan(2000, $duration, "Medium file dedupe too slow: {$duration}ms");
    }

    public function test_dedupe_performance_dupes_file(): void
    {
        $adif = new Adif;
        $adif->loadFile(testDataPath('dupes.adi'));
        $adif->parse();
        $doc = $adif->merge();

        $start = microtime(true);
        $doc->dedupe();
        $duration = (microtime(true) - $start) * 1000; // ms

        // Dupes file (6.8MB) with many duplicates should still be fast with O(n) optimization
        $this->assertLessThan(5000, $duration, "Dupes file dedupe too slow: {$duration}ms");
        $this->assertTrue($doc->hasDupes());
    }

    public function test_morph_performance_optimization(): void
    {
        // Morph also uses hash-based lookups now
        $adif = new Adif;
        $adif->loadFile(testDataPath('medium.adi'));
        $adif->parse();
        $doc = $adif->merge();

        $start = microtime(true);
        $doc->morph(Adif::MORPH_POTA_ONLY);
        $duration = (microtime(true) - $start) * 1000; // ms

        // Morph should be fast with hash-based filtering
        $this->assertLessThan(2000, $duration, "Morph operation too slow: {$duration}ms");
    }

    // Parse performance tests
    public function test_parse_performance_small_file(): void
    {
        $adif = new Adif;
        $adif->loadFile(testDataPath('small.adi'));

        $start = microtime(true);
        $adif->parse();
        $duration = (microtime(true) - $start) * 1000; // ms

        // Small file should parse quickly
        $this->assertLessThan(500, $duration, "Small file parsing too slow: {$duration}ms");
    }

    public function test_parse_performance_medium_file(): void
    {
        $adif = new Adif;
        $adif->loadFile(testDataPath('medium.adi'));

        $start = microtime(true);
        $adif->parse();
        $duration = (microtime(true) - $start) * 1000; // ms

        // Medium file should parse in < 1s
        $this->assertLessThan(1000, $duration, "Medium file parsing too slow: {$duration}ms");
    }

    // Sanitize performance tests
    public function test_sanitize_performance_medium_file(): void
    {
        $adif = new Adif;
        $adif->loadFile(testDataPath('medium.adi'));
        $adif->parse();
        $doc = $adif->merge();

        $start = microtime(true);
        $doc->sanitize();
        $duration = (microtime(true) - $start) * 1000; // ms

        // Sanitize should be fast
        $this->assertLessThan(1000, $duration, "Sanitize too slow: {$duration}ms");
    }

    // Validate performance tests
    public function test_validate_performance_medium_file(): void
    {
        $adif = new Adif;
        $adif->loadFile(testDataPath('medium.adi'));
        $adif->parse();
        $doc = $adif->merge();
        $doc->sanitize();

        $start = microtime(true);
        $doc->validate();
        $duration = (microtime(true) - $start) * 1000; // ms

        // Validation should complete in reasonable time
        $this->assertLessThan(2000, $duration, "Validate too slow: {$duration}ms");
    }

    // Complete workflow performance
    public function test_complete_workflow_performance_medium_file(): void
    {
        $adif = new Adif;
        $adif->loadFile(testDataPath('medium.adi'));

        $start = microtime(true);
        $adif->parse();
        $doc = $adif->merge();
        $doc->sanitize();
        $doc->validate();
        $doc->dedupe();
        $doc->morph(Adif::MORPH_POTA_ONLY);
        $duration = (microtime(true) - $start) * 1000; // ms

        // Complete workflow on medium file should finish in < 5s
        $this->assertLessThan(5000, $duration, "Complete workflow too slow: {$duration}ms");
    }

    // Output generation performance
    public function test_to_adif_performance(): void
    {
        $adif = new Adif;
        $adif->loadFile(testDataPath('medium.adi'));
        $adif->parse();
        $doc = $adif->merge();

        $start = microtime(true);
        $output = $doc->toAdif();
        $duration = (microtime(true) - $start) * 1000; // ms

        $this->assertLessThan(1000, $duration, "toAdif too slow: {$duration}ms");
        $this->assertNotEmpty($output);
    }

    public function test_to_json_performance(): void
    {
        $adif = new Adif;
        $adif->loadFile(testDataPath('medium.adi'));
        $adif->parse();
        $doc = $adif->merge();

        $start = microtime(true);
        $output = $doc->toJson();
        $duration = (microtime(true) - $start) * 1000; // ms

        $this->assertLessThan(500, $duration, "toJson too slow: {$duration}ms");
        $this->assertNotEmpty($output);
    }

    // Memory usage test
    public function test_memory_usage_medium_file(): void
    {
        $beforeMemory = memory_get_usage(true);

        $adif = new Adif;
        $adif->loadFile(testDataPath('medium.adi'));
        $adif->parse();
        $doc = $adif->merge();
        $doc->sanitize();

        $afterMemory = memory_get_usage(true);
        $memoryUsedMB = ($afterMemory - $beforeMemory) / 1024 / 1024;

        // Should not use excessive memory (< 128MB for medium file)
        $this->assertLessThan(128, $memoryUsedMB, "Memory usage too high: {$memoryUsedMB}MB");
    }

    // Large file tests (optional - only run if large.adi exists)
    public function test_parse_performance_large_file(): void
    {
        $path = testDataPath('large.adi');
        if (! file_exists($path)) {
            $this->markTestSkipped('large.adi not available');
        }

        $adif = new Adif;
        $adif->loadFile($path);

        $start = microtime(true);
        $adif->parse();
        $duration = (microtime(true) - $start) * 1000; // ms

        // 14MB file should parse in < 5s
        $this->assertLessThan(5000, $duration, "Large file parsing too slow: {$duration}ms");
    }

    public function test_dedupe_performance_large_file(): void
    {
        $path = testDataPath('large.adi');
        if (! file_exists($path)) {
            $this->markTestSkipped('large.adi not available');
        }

        $adif = new Adif;
        $adif->loadFile($path);
        $adif->parse();
        $doc = $adif->merge();

        $start = microtime(true);
        $doc->dedupe();
        $duration = (microtime(true) - $start) * 1000; // ms

        // Large file (14MB) should complete with O(n) optimization
        $this->assertLessThan(10000, $duration, "Large file dedupe too slow: {$duration}ms");
    }
}
