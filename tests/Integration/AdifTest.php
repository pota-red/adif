<?php

declare(strict_types=1);

namespace Pota\Adif\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Pota\Adif\Adif;
use Pota\Adif\Document;

final class AdifTest extends TestCase
{
    public function test_load_file(): void
    {
        $adif = new Adif;
        $index = $adif->loadFile(testDataPath('p75.adi'));

        $this->assertEquals(0, $index);
    }

    public function test_load_string(): void
    {
        $adif = new Adif;
        $text = "Test\n<eoh>\n<call:5>W1AW<eor>";
        $index = $adif->loadString($text);

        $this->assertEquals(0, $index);
    }

    public function test_load_multiple_files(): void
    {
        $adif = new Adif;
        $idx1 = $adif->loadFile(testDataPath('p75.adi'));
        $idx2 = $adif->loadFile(testDataPath('small.adi'));

        $this->assertEquals(0, $idx1);
        $this->assertEquals(1, $idx2);
    }

    public function test_parse_single_document(): void
    {
        $this->expectNotToPerformAssertions();

        $adif = new Adif;
        $adif->loadFile(testDataPath('p75.adi'));
        $adif->parse();
    }

    public function test_parse_multiple_documents(): void
    {
        $this->expectNotToPerformAssertions();

        $adif = new Adif;
        $adif->loadFile(testDataPath('p75.adi'));
        $adif->loadFile(testDataPath('small.adi'));
        $adif->parse();
    }

    public function test_merge_single_document(): void
    {
        $adif = new Adif;
        $adif->loadFile(testDataPath('p75.adi'));
        $adif->parse();

        $doc = $adif->merge();

        $this->assertInstanceOf(Document::class, $doc);
        // p75.adi actually contains 50 entries
        $this->assertEquals(50, $doc->count);
    }

    public function test_merge_multiple_documents(): void
    {
        $adif = new Adif;
        $adif->loadFile(testDataPath('p75.adi'));
        $adif->loadString("Test\n<eoh>\n<call:5>W1AW<eor>\n<call:5>K1ABC<eor>");
        $adif->parse();

        $doc = $adif->merge();

        $this->assertInstanceOf(Document::class, $doc);
        // p75.adi has 50 entries + 2 from string = 52
        $this->assertEquals(52, $doc->count);
    }

    public function test_merge_preserves_metadata(): void
    {
        $adif = new Adif;
        $adif->loadFile(testDataPath('p75.adi'));
        $adif->loadFile(testDataPath('small.adi'));
        $adif->parse();

        $doc = $adif->merge();
        $json = json_decode($doc->toJson(), true);

        $this->assertArrayHasKey('sources', $json['meta']);
        $this->assertCount(2, $json['meta']['sources']);
    }

    public function test_merge_aggregates_timers(): void
    {
        $adif = new Adif;
        $adif->loadFile(testDataPath('p75.adi'));
        $adif->loadFile(testDataPath('small.adi'));
        $adif->parse();

        $doc = $adif->merge();
        $timers = $doc->getTimers();

        $this->assertArrayHasKey('parse', $timers);
        $this->assertGreaterThan(0, $timers['parse']);
    }

    public function test_workflow_load_parse_validate(): void
    {
        $this->expectNotToPerformAssertions();

        $adif = new Adif;
        $adif->loadFile(testDataPath('p75.adi'));
        $adif->parse();

        $doc = $adif->merge();
        $doc->sanitize();
        $doc->validate();
    }

    public function test_workflow_load_parse_dedupe(): void
    {
        $adif = new Adif;
        $adif->loadFile(testDataPath('p75_dupes.adi'));
        $adif->parse();

        $doc = $adif->merge();
        $originalCount = $doc->count;
        $doc->dedupe();

        $this->assertLessThan($originalCount, $doc->count);
        $this->assertTrue($doc->hasDupes());
    }

    public function test_workflow_load_parse_morph(): void
    {
        $adif = new Adif;
        $adif->loadFile(testDataPath('p75.adi'));
        $adif->parse();

        $doc = $adif->merge();
        $doc->morph(Adif::MORPH_POTA_ONLY);

        // Should filter to POTA fields only
        $entries = $doc->getEntries();
        $this->assertNotEmpty($entries);
    }

    public function test_workflow_complete_processing(): void
    {
        $adif = new Adif;
        $adif->loadFile(testDataPath('p75.adi'));
        $adif->parse();

        $doc = $adif->merge();
        $doc->sanitize();
        $doc->validate();
        $doc->dedupe();
        $doc->morph(Adif::MORPH_POTA_ONLY);

        $adifOutput = $doc->toAdif();
        $jsonOutput = $doc->toJson();

        $this->assertNotEmpty($adifOutput);
        $this->assertNotEmpty($jsonOutput);
    }

    public function test_merge_with_pota_fer_file(): void
    {
        $adif = new Adif;
        $adif->loadFile(testDataPath('pota_fer.adi'));
        $adif->parse();

        $doc = $adif->merge();
        $originalCount = $doc->count;
        $doc->morph(Adif::MORPH_POTA_REFS);

        // POTA FER file should expand entries
        $this->assertGreaterThanOrEqual($originalCount, $doc->count);
    }

    public function test_constants(): void
    {
        $this->assertEquals(3000000, Adif::CHUNK_MAX_SIZE);
        $this->assertEquals(1, Adif::MORPH_ADIF_STRICT);
        $this->assertEquals(2, Adif::MORPH_POTA_ONLY);
        $this->assertEquals(3, Adif::MORPH_POTA_REFS);
    }

    public function test_load_mixed_sources(): void
    {
        $adif = new Adif;
        $adif->loadFile(testDataPath('p75.adi'));
        $adif->loadString("Test\n<eoh>\n<call:5>W1AW<eor>");
        $adif->parse();

        $doc = $adif->merge();
        $json = json_decode($doc->toJson(), true);

        // p75.adi has 50 entries + 1 from string = 51
        $this->assertEquals(51, $doc->count);
        $this->assertCount(2, $json['meta']['sources']);
    }

    public function test_adif_round_trip(): void
    {
        $adif1 = new Adif;
        $adif1->loadFile(testDataPath('p75.adi'));
        $adif1->parse();
        $doc1 = $adif1->merge();

        $adifString = $doc1->toAdif();

        $adif2 = new Adif;
        $adif2->loadString($adifString);
        $adif2->parse();
        $doc2 = $adif2->merge();

        $this->assertEquals($doc1->count, $doc2->count);
    }

    public function test_json_output_structure(): void
    {
        $adif = new Adif;
        $adif->loadFile(testDataPath('p75.adi'));
        $adif->parse();

        $doc = $adif->merge();
        $doc->sanitize();
        $doc->validate();
        $doc->dedupe();

        $json = json_decode($doc->toJson(), true);

        // Verify complete JSON structure
        $this->assertArrayHasKey('meta', $json);
        $this->assertArrayHasKey('count', $json['meta']);
        $this->assertArrayHasKey('duplicates', $json['meta']);
        $this->assertArrayHasKey('errors', $json['meta']);
        $this->assertArrayHasKey('entries', $json);
        $this->assertArrayHasKey('timers', $json);
    }

    public function test_timer_tracking(): void
    {
        $adif = new Adif;
        $adif->loadFile(testDataPath('small.adi'));
        $adif->parse();

        $doc = $adif->merge();
        $doc->sanitize();
        $doc->validate();
        $doc->dedupe();

        $timers = $doc->getTimers();
        $this->assertArrayHasKey('parse', $timers);
        $this->assertArrayHasKey('sanitize', $timers);
        $this->assertArrayHasKey('validate', $timers);
        $this->assertArrayHasKey('dedupe', $timers);
        $this->assertArrayHasKey('total', $timers);

        // All timers should be positive
        foreach ($timers as $name => $value) {
            $this->assertGreaterThan(0, $value, "Timer $name should be > 0");
        }
    }
}
