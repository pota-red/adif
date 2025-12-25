<?php

declare(strict_types=1);

namespace Pota\Adif\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Pota\Adif\Document;
use Pota\Adif\Adif;

final class DocumentTest extends TestCase
{
    // Construction tests
    public function testConstructsFromFile(): void
    {
        $path = testDataPath('p75.adi');
        $doc = new Document($path);

        $this->assertEquals('p75.adi', $doc->filename);
        $this->assertEquals(0, $doc->count); // Not parsed yet
    }

    public function testConstructsFromString(): void
    {
        $adif = "Test header\n<eoh>\n<call:5>W1AW<band:3>20M<mode:3>SSB<eor>";
        $doc = new Document($adif);

        $this->assertEquals('', $doc->filename);
        $this->assertEquals(0, $doc->count); // Not parsed yet
    }

    public function testConstructsEmptyDocument(): void
    {
        $doc = new Document();
        $this->assertEquals(0, $doc->count);
    }

    public function testThrowsExceptionForInvalidFile(): void
    {
        $this->expectException(\Exception::class);
        // Non-existent files throw the generic "Unable to create instance" exception
        // since the path doesn't contain <eor> and isn't a valid file
        $this->expectExceptionMessage('Unable to create instance');

        new Document('/nonexistent/file.adi');
    }

    public function testThrowsExceptionForInvalidData(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Unable to create instance');

        new Document('random string without eor');
    }

    // Parsing tests
    public function testParsesSimpleFile(): void
    {
        $path = testDataPath('small.adi');
        $doc = new Document($path);
        $doc->parse();

        $this->assertGreaterThan(0, $doc->count);
    }

    public function testParsesP75File(): void
    {
        $path = testDataPath('p75.adi');
        $doc = new Document($path);
        $doc->parse();

        // p75.adi actually contains 50 entries
        $this->assertEquals(50, $doc->count);
    }

    public function testParseExtractsHeaders(): void
    {
        $adif = "ADIF Export\n<programid:8>Test App<programversion:5>1.0.0<eoh>\n<call:5>W1AW<eor>";
        $doc = new Document($adif);
        $doc->parse();

        $headers = $doc->getHeaders();
        $this->assertEquals('Test App', $headers['programid']);
        $this->assertEquals('1.0.0', $headers['programversion']);
    }

    public function testParseExtractsEntries(): void
    {
        $adif = "Test\n<eoh>\n<call:5>W1AW<band:3>20M<mode:3>SSB<eor>";
        $doc = new Document($adif);
        $doc->parse();

        $entries = $doc->getEntries();
        $this->assertCount(1, $entries);
        $this->assertEquals('w1aw', $entries[0]['call']);
        $this->assertEquals('20m', $entries[0]['band']);
    }

    public function testParseHandlesMultipleEntries(): void
    {
        $adif = "Test\n<eoh>\n" .
                "<call:5>W1AW<band:3>20M<eor>\n" .
                "<call:5>K1ABC<band:3>40M<eor>";
        $doc = new Document($adif);
        $doc->parse();

        $this->assertEquals(2, $doc->count);
    }

    public function testParseWithoutHeaderSection(): void
    {
        $adif = "<call:5>W1AW<band:3>20M<eor>";
        $doc = new Document($adif);
        $doc->parse();

        $this->assertEquals(1, $doc->count);
    }

    public function testParseThrowsOnMalformedInput(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Malformed input');

        $doc = new Document();
        $doc->fromString("no tags at all");
        $doc->parse();
    }

    // First/last entry tracking
    public function testTracksFirstAndLastEntry(): void
    {
        $adif = "Test\n<eoh>\n" .
                "<call:5>W1AW<qso_date:8>20231225<time_on:6>120000<eor>\n" .
                "<call:5>K1ABC<qso_date:8>20231225<time_on:6>100000<eor>\n" .
                "<call:5>N1XYZ<qso_date:8>20231225<time_on:6>140000<eor>";
        $doc = new Document($adif);
        $doc->parse();

        $first = $doc->getFirstEntry();
        $last = $doc->getLastEntry();

        // Parsing lowercases all values
        $this->assertEquals('k1abc', $first['call']); // 1000 is earliest
        $this->assertEquals('n1xyz', $last['call']); // 1400 is latest
    }

    public function testReturnsNullForFirstLastEntryWhenEmpty(): void
    {
        $doc = new Document();
        $this->assertNull($doc->getFirstEntry());
        $this->assertNull($doc->getLastEntry());
    }

    // Sanitization tests
    public function testSanitizeNormalizesData(): void
    {
        $adif = "Test\n<eoh>\n<call:5>w1aw<band:2>20m<mode:3>usb<eor>";
        $doc = new Document($adif);
        $doc->parse();
        $doc->sanitize();

        $entries = $doc->getEntries();
        $this->assertEquals('W1AW', $entries[0]['call']);
        $this->assertEquals('20M', $entries[0]['band']);
        $this->assertEquals('SSB', $entries[0]['mode']);
        $this->assertEquals('USB', $entries[0]['submode']);
    }

    // Validation tests
    public function testValidateDetectsErrors(): void
    {
        $adif = "Test\n<eoh>\n<call:5>W1AW<band:7>INVALID<mode:3>XXX<eor>";
        $doc = new Document($adif);
        $doc->parse();
        $doc->sanitize();
        $doc->validate();

        $this->assertTrue($doc->hasErrors());
        $errors = $doc->getErrors();
        $this->assertNotEmpty($errors);
    }

    public function testValidateWithPotaMode(): void
    {
        $adif = "Test\n<eoh>\n<call:5>W1AW<band:3>20M<eor>";
        $doc = new Document($adif);
        $doc->setMode(Document::MODE_POTA);
        $doc->parse();
        $doc->sanitize();
        $doc->validate();

        $this->assertTrue($doc->hasErrors());
    }

    public function testDisableQpsCheck(): void
    {
        // Create entries at same timestamp
        $entries = [];
        for ($i = 0; $i < 100; $i++) {
            $entries[] = "<call:5>W1AW<qso_date:8>20231225<time_on:4>1200<eor>";
        }
        $adif = "Test\n<eoh>\n" . implode("\n", $entries);

        $doc = new Document($adif);
        $doc->checkQps(false);
        $doc->parse();
        $doc->validate();

        $errors = $doc->getErrors();
        if (isset($errors['@'])) {
            $this->assertNotContains('qps', $errors['@']);
        } else {
            $this->assertArrayNotHasKey('@', $errors);
        }
    }

    // Deduplication tests
    public function testDedupeRemovesDuplicates(): void
    {
        $path = testDataPath('p75_dupes.adi');
        $doc = new Document($path);
        $doc->parse();

        $originalCount = $doc->count;
        $doc->dedupe();

        $this->assertLessThan($originalCount, $doc->count);
        $this->assertTrue($doc->hasDupes());
        $this->assertNotEmpty($doc->getDupes());
    }

    public function testDedupeWithNoDuplicates(): void
    {
        $adif = "Test\n<eoh>\n" .
                "<call:5>W1AW<band:3>20M<qso_date:8>20231225<time_on:4>1200<mode:3>SSB<operator:4>W1AW<pota_my_park_ref:7>US-0001<eor>\n" .
                "<call:5>K1ABC<band:3>40M<qso_date:8>20231225<time_on:4>1300<mode:3>CW<operator:4>W1AW<pota_my_park_ref:7>US-0001<eor>";
        $doc = new Document($adif);
        $doc->parse();

        $originalCount = $doc->count;
        $doc->dedupe();

        $this->assertEquals($originalCount, $doc->count);
        $this->assertFalse($doc->hasDupes());
    }

    // Morph tests
    public function testMorphAdifStrict(): void
    {
        $adif = "Test\n<eoh>\n<call:5>W1AW<band:3>20M<custom_field:5>value<eor>";
        $doc = new Document($adif);
        $doc->parse();
        $doc->morph(Adif::MORPH_ADIF_STRICT);

        $entries = $doc->getEntries();
        $this->assertArrayHasKey('call', $entries[0]);
        $this->assertArrayHasKey('band', $entries[0]);
        $this->assertArrayNotHasKey('custom_field', $entries[0]);
    }

    public function testMorphPotaOnly(): void
    {
        $adif = "Test\n<eoh>\n<call:5>W1AW<band:3>20M<age:2>42<pota_ref:7>US-0001<eor>";
        $doc = new Document($adif);
        $doc->parse();
        $doc->morph(Adif::MORPH_POTA_ONLY);

        $entries = $doc->getEntries();
        $this->assertArrayHasKey('call', $entries[0]);
        $this->assertArrayHasKey('pota_ref', $entries[0]);
        $this->assertArrayNotHasKey('age', $entries[0]);
    }

    public function testMorphPotaRefsUnrollsSingleRef(): void
    {
        $adif = "Test\n<eoh>\n<call:5>W1AW<my_pota_ref:7>US-0001<eor>";
        $doc = new Document($adif);
        $doc->parse();
        $doc->morph(Adif::MORPH_POTA_REFS);

        $entries = $doc->getEntries();
        $this->assertArrayHasKey('pota_my_park_ref', $entries[0]);
        // Parsing lowercases values
        $this->assertEquals('us-0001', $entries[0]['pota_my_park_ref']);
    }

    public function testMorphPotaRefsUnrollsMultipleActivatorRefs(): void
    {
        $adif = "Test\n<eoh>\n<call:5>W1AW<my_pota_ref:15>US-0001,US-0002<eor>";
        $doc = new Document($adif);
        $doc->parse();
        $originalCount = $doc->count;
        $doc->morph(Adif::MORPH_POTA_REFS);

        $this->assertEquals($originalCount * 2, $doc->count);
    }

    public function testMorphPotaRefsHandlesLocationSuffix(): void
    {
        $adif = "Test\n<eoh>\n<call:5>W1AW<my_pota_ref:10>US-0001@WA<eor>";
        $doc = new Document($adif);
        $doc->parse();
        $doc->morph(Adif::MORPH_POTA_REFS);

        $entries = $doc->getEntries();
        // Parsing lowercases values
        $this->assertEquals('us-0001', $entries[0]['pota_my_park_ref']);
        $this->assertEquals('wa', $entries[0]['pota_my_location']);
    }

    // Chunking tests
    public function testChunkKeepsSmallDocumentIntact(): void
    {
        $path = testDataPath('p75.adi');
        $doc = new Document($path);
        $doc->parse();
        $doc->chunk();

        $json = json_decode($doc->toJson(), true);
        $this->assertEquals(1, $json['meta']['chunks']);
    }

    // Output generation tests
    public function testToAdifGeneratesValidAdif(): void
    {
        $path = testDataPath('p75.adi');
        $doc = new Document($path);
        $doc->parse();

        $adif = $doc->toAdif();

        $this->assertStringContainsString('<eoh>', $adif);
        $this->assertStringContainsString('<eor>', $adif);
        $this->assertStringContainsString('<adif_version:', $adif);
        $this->assertStringContainsString('POTA-ADIF', $adif);
    }

    public function testToJsonGeneratesValidJson(): void
    {
        $path = testDataPath('p75.adi');
        $doc = new Document($path);
        $doc->parse();

        $json = $doc->toJson();
        $data = json_decode($json, true);

        $this->assertNotNull($data);
        $this->assertArrayHasKey('meta', $data);
        $this->assertArrayHasKey('entries', $data);
        $this->assertArrayHasKey('timers', $data);
        // p75.adi actually contains 50 entries
        $this->assertEquals(50, $data['meta']['count']);
    }

    public function testToJsonPrettyPrint(): void
    {
        $adif = "Test\n<eoh>\n<call:5>W1AW<eor>";
        $doc = new Document($adif);
        $doc->parse();

        $json = $doc->toJson(true);

        $this->assertStringContainsString("\n", $json);
        $this->assertStringContainsString("    ", $json); // Indentation
    }

    // Timer tests
    public function testRecordsTimersForOperations(): void
    {
        $path = testDataPath('small.adi');
        $doc = new Document($path);
        $doc->parse();
        $doc->sanitize();
        $doc->validate();
        $doc->dedupe();

        $timers = $doc->getTimers();
        $this->assertArrayHasKey('parse', $timers);
        $this->assertArrayHasKey('sanitize', $timers);
        $this->assertArrayHasKey('validate', $timers);
        $this->assertArrayHasKey('dedupe', $timers);
        $this->assertArrayHasKey('total', $timers);
    }

    public function testAddTimerAggregatesValues(): void
    {
        $doc = new Document();
        $doc->addTimer('test', 1.5);
        $doc->addTimer('test', 2.5);

        $timers = $doc->getTimers();
        $this->assertEquals(4.0, $timers['test']);
    }

    public function testSumTimersReturnsTotal(): void
    {
        $path = testDataPath('small.adi');
        $doc = new Document($path);
        $doc->parse();
        $doc->sanitize();

        $total = $doc->sumTimers();
        $this->assertGreaterThan(0, $total);
    }

    // Override field tests
    public function testOverrideFieldAppliesValue(): void
    {
        $adif = "Test\n<eoh>\n<call:5>W1AW<operator:5>K1ABC<eor>";
        $doc = new Document($adif);
        $doc->overrideField('operator', 'N1XYZ');
        $doc->parse();

        $entries = $doc->getEntries();
        // Override values preserve their original case
        $this->assertEquals('N1XYZ', $entries[0]['operator']);
    }

    // Linting tests
    public function testLintDetectsFormatIssues(): void
    {
        $doc = new Document();
        $doc->fromString("no eoh or eor tags");

        $errors = $doc->lint();
        $this->assertNotEmpty($errors);
    }

    public function testLintAcceptsValidFormat(): void
    {
        $adif = "Test\n<eoh>\n<call:5>W1AW<eor>";
        $doc = new Document($adif);

        $errors = $doc->lint();
        $this->assertEmpty($errors);
    }

    // Header and entry manipulation
    public function testAddHeader(): void
    {
        $doc = new Document();
        $doc->addHeader('programid', 'TestApp');
        $doc->addHeader('Custom header line');

        $headers = $doc->getHeaders();
        $this->assertEquals('TestApp', $headers['programid']);
    }

    public function testGetSpecificHeader(): void
    {
        $adif = "Test\n<programid:7>TestApp<eoh>\n<call:5>W1AW<eor>";
        $doc = new Document($adif);
        $doc->parse();

        $programId = $doc->getHeaders('programid');
        $this->assertEquals('TestApp', $programId);
    }

    public function testGetNonExistentHeaderReturnsEmptyString(): void
    {
        $doc = new Document();
        $result = $doc->getHeaders('nonexistent');
        $this->assertEquals('', $result);
    }

    public function testAddEntry(): void
    {
        $doc = new Document();
        $doc->addEntry(['call' => 'W1AW', 'band' => '20M']);

        $this->assertEquals(1, $doc->count);
        $entries = $doc->getEntries();
        $this->assertEquals('W1AW', $entries[0]['call']);
    }

    public function testAddMultipleEntries(): void
    {
        $doc = new Document();
        $doc->addEntry([
            ['call' => 'W1AW', 'band' => '20M'],
            ['call' => 'K1ABC', 'band' => '40M'],
        ]);

        $this->assertEquals(2, $doc->count);
    }

    public function testRemoveEntry(): void
    {
        $doc = new Document();
        $doc->addEntry(['call' => 'W1AW']);
        $doc->addEntry(['call' => 'K1ABC']);
        $doc->removeEntry(0);

        $this->assertEquals(1, $doc->count);
    }

    // Mode tests
    public function testSetModeDefault(): void
    {
        $this->expectNotToPerformAssertions();

        $doc = new Document();
        $doc->setMode(Document::MODE_DEFAULT);
    }

    public function testSetModePota(): void
    {
        $this->expectNotToPerformAssertions();

        $doc = new Document();
        $doc->setMode(Document::MODE_POTA);
    }

    // From file/string tests
    public function testFromFile(): void
    {
        $doc = new Document();
        $doc->fromFile(testDataPath('p75.adi'));

        $this->assertEquals('p75.adi', $doc->filename);
    }

    public function testFromFileThrowsForInvalidPath(): void
    {
        $this->expectException(\Exception::class);

        $doc = new Document();
        $doc->fromFile('/nonexistent/path.adi');
    }

    public function testFromString(): void
    {
        $doc = new Document();
        $doc->fromString("<call:5>W1AW<eor>");

        // Document should be ready for parsing
        $doc->parse();
        $this->assertEquals(1, $doc->count);
    }

    // Source tracking
    public function testAddSource(): void
    {
        $doc = new Document();
        $doc->addSource(['fn=test.adi', 'ec=10']);

        $json = json_decode($doc->toJson(), true);
        $this->assertArrayHasKey('sources', $json['meta']);
        $this->assertCount(1, $json['meta']['sources']);
    }

    // =========================================================================
    // Additional tests for improved coverage
    // =========================================================================

    // unroll_pota_refs tests - covering various branches

    public function testUnrollPotaRefsBothSidesInFer(): void
    {
        // Both activator and hunter have comma-separated park refs (both in -fers)
        $adif = "Test\n<eoh>\n<call:5>W1AW<my_pota_ref:15>US-0001,US-0002<pota_ref:15>US-0003,US-0004<eor>";
        $doc = new Document($adif);
        $doc->parse();

        $originalCount = $doc->count;
        $this->assertEquals(1, $originalCount);

        $doc->morph(Adif::MORPH_POTA_REFS);

        // Should create 2 activator refs x 2 hunter refs = 4 entries
        $this->assertEquals(4, $doc->count);

        $entries = $doc->getEntries();
        // Verify each combination exists
        $parks = [];
        foreach ($entries as $entry) {
            $parks[] = $entry['pota_my_park_ref'] . '-' . $entry['pota_park_ref'];
        }
        $this->assertContains('us-0001-us-0003', $parks);
        $this->assertContains('us-0001-us-0004', $parks);
        $this->assertContains('us-0002-us-0003', $parks);
        $this->assertContains('us-0002-us-0004', $parks);
    }

    public function testUnrollPotaRefsBothSidesInFerWithLocations(): void
    {
        // Both sides with comma-separated refs AND location suffixes
        $adif = "Test\n<eoh>\n<call:5>W1AW<my_pota_ref:21>US-0001@WA,US-0002@OR<pota_ref:21>US-0003@CA,US-0004@NV<eor>";
        $doc = new Document($adif);
        $doc->parse();
        $doc->morph(Adif::MORPH_POTA_REFS);

        $this->assertEquals(4, $doc->count);

        $entries = $doc->getEntries();
        // Check that locations are extracted properly
        $hasMyLocation = false;
        $hasTheirLocation = false;
        foreach ($entries as $entry) {
            if (isset($entry['pota_my_location'])) {
                $hasMyLocation = true;
                $this->assertContains($entry['pota_my_location'], ['wa', 'or']);
            }
            if (isset($entry['pota_location'])) {
                $hasTheirLocation = true;
                $this->assertContains($entry['pota_location'], ['ca', 'nv']);
            }
        }
        $this->assertTrue($hasMyLocation);
        $this->assertTrue($hasTheirLocation);
    }

    public function testUnrollPotaRefsOnlyHunterSideInFer(): void
    {
        // Only hunter side has comma-separated refs (hunter-only -fer)
        $adif = "Test\n<eoh>\n<call:5>W1AW<my_pota_ref:7>US-0001<pota_ref:15>US-0003,US-0004<eor>";
        $doc = new Document($adif);
        $doc->parse();

        $originalCount = $doc->count;
        $this->assertEquals(1, $originalCount);

        $doc->morph(Adif::MORPH_POTA_REFS);

        // Should create 1 activator ref x 2 hunter refs = 2 entries
        $this->assertEquals(2, $doc->count);

        $entries = $doc->getEntries();
        foreach ($entries as $entry) {
            $this->assertEquals('us-0001', $entry['pota_my_park_ref']);
            $this->assertContains($entry['pota_park_ref'], ['us-0003', 'us-0004']);
        }
    }

    public function testUnrollPotaRefsOnlyHunterSideInFerWithLocation(): void
    {
        // Hunter side has comma-separated refs with locations, activator has single ref with location
        $adif = "Test\n<eoh>\n<call:5>W1AW<my_pota_ref:10>US-0001@WA<pota_ref:21>US-0003@CA,US-0004@NV<eor>";
        $doc = new Document($adif);
        $doc->parse();
        $doc->morph(Adif::MORPH_POTA_REFS);

        $this->assertEquals(2, $doc->count);

        $entries = $doc->getEntries();
        foreach ($entries as $entry) {
            $this->assertEquals('us-0001', $entry['pota_my_park_ref']);
            $this->assertEquals('wa', $entry['pota_my_location']);
            $this->assertContains($entry['pota_park_ref'], ['us-0003', 'us-0004']);
            $this->assertContains($entry['pota_location'], ['ca', 'nv']);
        }
    }

    public function testUnrollPotaRefsActivatorFerWithHunterLocation(): void
    {
        // Activator side has multiple parks, hunter has single park with location
        $adif = "Test\n<eoh>\n<call:5>W1AW<my_pota_ref:15>US-0001,US-0002<pota_ref:10>US-0003@CA<eor>";
        $doc = new Document($adif);
        $doc->parse();
        $doc->morph(Adif::MORPH_POTA_REFS);

        $this->assertEquals(2, $doc->count);

        $entries = $doc->getEntries();
        foreach ($entries as $entry) {
            $this->assertContains($entry['pota_my_park_ref'], ['us-0001', 'us-0002']);
            $this->assertEquals('us-0003', $entry['pota_park_ref']);
            $this->assertEquals('ca', $entry['pota_location']);
        }
    }

    public function testUnrollPotaRefsHunterLocationOnly(): void
    {
        // No -fers, but hunter has @location suffix
        $adif = "Test\n<eoh>\n<call:5>W1AW<my_pota_ref:7>US-0001<pota_ref:10>US-0003@CA<eor>";
        $doc = new Document($adif);
        $doc->parse();
        $doc->morph(Adif::MORPH_POTA_REFS);

        $this->assertEquals(1, $doc->count);

        $entries = $doc->getEntries();
        $this->assertEquals('us-0001', $entries[0]['pota_my_park_ref']);
        $this->assertEquals('us-0003', $entries[0]['pota_park_ref']);
        $this->assertEquals('ca', $entries[0]['pota_location']);
    }

    public function testUnrollPotaRefsSigInfoToMyPotaRef(): void
    {
        // my_sig_info should be converted to my_pota_ref when my_pota_ref is not present
        $adif = "Test\n<eoh>\n<call:5>W1AW<my_sig_info:7>US-0001<eor>";
        $doc = new Document($adif);
        $doc->parse();
        $doc->morph(Adif::MORPH_POTA_REFS);

        $entries = $doc->getEntries();
        $this->assertEquals('us-0001', $entries[0]['pota_my_park_ref']);
    }

    public function testUnrollPotaRefsSigInfoToPotaRef(): void
    {
        // sig_info should be converted to pota_ref when pota_ref is not present
        $adif = "Test\n<eoh>\n<call:5>W1AW<sig_info:7>US-0003<eor>";
        $doc = new Document($adif);
        $doc->parse();
        $doc->morph(Adif::MORPH_POTA_REFS);

        $entries = $doc->getEntries();
        $this->assertEquals('us-0003', $entries[0]['pota_park_ref']);
    }

    public function testUnrollPotaRefsSigInfoNotOverwritingExisting(): void
    {
        // When both my_sig_info and my_pota_ref are present, my_pota_ref takes precedence
        $adif = "Test\n<eoh>\n<call:5>W1AW<my_sig_info:7>US-9999<my_pota_ref:7>US-0001<eor>";
        $doc = new Document($adif);
        $doc->parse();
        $doc->morph(Adif::MORPH_POTA_REFS);

        $entries = $doc->getEntries();
        $this->assertEquals('us-0001', $entries[0]['pota_my_park_ref']);
    }

    public function testUnrollPotaRefsNoPotaFields(): void
    {
        // Entry without any pota fields should remain unchanged
        $adif = "Test\n<eoh>\n<call:5>W1AW<band:3>20M<eor>";
        $doc = new Document($adif);
        $doc->parse();

        $originalCount = $doc->count;
        $doc->morph(Adif::MORPH_POTA_REFS);

        $this->assertEquals($originalCount, $doc->count);
        $entries = $doc->getEntries();
        $this->assertArrayNotHasKey('pota_my_park_ref', $entries[0]);
        $this->assertArrayNotHasKey('pota_park_ref', $entries[0]);
    }

    public function testUnrollPotaRefsActivatorFerWithLocationNoHunter(): void
    {
        // Activator side has multiple parks with locations, no hunter ref
        $adif = "Test\n<eoh>\n<call:5>W1AW<my_pota_ref:21>US-0001@WA,US-0002@OR<eor>";
        $doc = new Document($adif);
        $doc->parse();
        $doc->morph(Adif::MORPH_POTA_REFS);

        $this->assertEquals(2, $doc->count);

        $entries = $doc->getEntries();
        $locations = [];
        $parks = [];
        foreach ($entries as $entry) {
            $parks[] = $entry['pota_my_park_ref'];
            if (isset($entry['pota_my_location'])) {
                $locations[] = $entry['pota_my_location'];
            }
        }
        $this->assertContains('us-0001', $parks);
        $this->assertContains('us-0002', $parks);
        $this->assertContains('wa', $locations);
        $this->assertContains('or', $locations);
    }

    // chunk() tests - covering large document chunking

    public function testChunkSplitsLargeDocument(): void
    {
        // Create a document with enough entries to exceed chunk size
        $doc = new Document();

        // Add many entries with substantial data to ensure we exceed chunk size
        for ($i = 0; $i < 500; $i++) {
            $doc->addEntry([
                'call' => 'W' . str_pad((string)$i, 4, '0', STR_PAD_LEFT),
                'band' => '20M',
                'mode' => 'SSB',
                'qso_date' => '20231225',
                'time_on' => '120000',
                'freq' => '14.250',
                'rst_sent' => '59',
                'rst_rcvd' => '59',
                'my_pota_ref' => 'US-0001',
                'pota_ref' => 'US-0002',
                'operator' => 'W1AW',
                'station_callsign' => 'W1AW',
                'my_gridsquare' => 'FN31',
                'gridsquare' => 'FN42',
                'comment' => 'This is a test comment with some additional text to increase size',
            ]);
        }

        // Chunk with a small size to force multiple chunks
        $doc->chunk(5000);

        $json = json_decode($doc->toJson(), true);
        $this->assertGreaterThan(1, $json['meta']['chunks']);
        $this->assertIsArray($json['entries']);

        // Each chunk should be an array of entries
        foreach ($json['entries'] as $chunk) {
            $this->assertIsArray($chunk);
            $this->assertNotEmpty($chunk);
        }
    }

    public function testChunkWithEmptyDocument(): void
    {
        $doc = new Document();
        $doc->chunk();

        $json = json_decode($doc->toJson(), true);
        // Empty document should still have entries array
        $this->assertArrayHasKey('entries', $json);
    }

    public function testChunkPreservesAllEntries(): void
    {
        $doc = new Document();

        // Add entries
        $entryCount = 100;
        for ($i = 0; $i < $entryCount; $i++) {
            $doc->addEntry([
                'call' => 'W' . str_pad((string)$i, 4, '0', STR_PAD_LEFT),
                'band' => '20M',
                'comment' => str_repeat('x', 100), // Pad to increase size
            ]);
        }

        // Chunk with small size
        $doc->chunk(1000);

        $json = json_decode($doc->toJson(), true);

        // Count total entries across all chunks
        $totalEntries = 0;
        foreach ($json['entries'] as $chunk) {
            $totalEntries += count($chunk);
        }

        $this->assertEquals($entryCount, $totalEntries);
    }

    // generateAdifHeaders() tests - covering sources output

    public function testGenerateAdifHeadersWithMultipleSources(): void
    {
        $doc = new Document();
        $doc->addSource(['fn=file1.adi', 'ec=10']);
        $doc->addSource(['fn=file2.adi', 'ec=20']);
        $doc->addEntry(['call' => 'W1AW']);

        $adif = $doc->toAdif();

        // Should contain source lines
        $this->assertStringContainsString('Source [fn=file1.adi, ec=10]', $adif);
        $this->assertStringContainsString('Source [fn=file2.adi, ec=20]', $adif);
    }

    public function testGenerateAdifHeadersWithNoSources(): void
    {
        $doc = new Document();
        $doc->addEntry(['call' => 'W1AW']);

        $adif = $doc->toAdif();

        // Should not contain Source lines
        $this->assertStringNotContainsString('Source [', $adif);
        // But should still have standard headers
        $this->assertStringContainsString('<adif_version:', $adif);
        $this->assertStringContainsString('<programid:', $adif);
    }

    // Additional edge case tests

    public function testSetModeWithInvalidMode(): void
    {
        $this->expectNotToPerformAssertions();

        $doc = new Document();
        $doc->setMode(999); // Invalid mode
    }

    public function testToJsonWithDuplicatesAndErrors(): void
    {
        // Test that duplicates and errors are included in JSON output
        $adif = "Test\n<eoh>\n" .
                "<call:5>W1AW<band:3>20M<qso_date:8>20231225<time_on:4>1200<mode:3>SSB<operator:4>W1AW<pota_my_park_ref:7>US-0001<eor>\n" .
                "<call:5>W1AW<band:3>20M<qso_date:8>20231225<time_on:4>1200<mode:3>SSB<operator:4>W1AW<pota_my_park_ref:7>US-0001<eor>"; // Duplicate

        $doc = new Document($adif);
        $doc->parse();
        $doc->dedupe();

        $json = json_decode($doc->toJson(), true);

        $this->assertArrayHasKey('duplicates', $json);
        $this->assertNotEmpty($json['duplicates']);
    }

    public function testToJsonWithHeaders(): void
    {
        $adif = "Test header\n<programid:7>TestApp<programversion:3>1.0<eoh>\n<call:5>W1AW<eor>";
        $doc = new Document($adif);
        $doc->parse();

        $json = json_decode($doc->toJson(), true);

        $this->assertArrayHasKey('headers', $json);
        $this->assertEquals('TestApp', $json['headers']['programid']);
    }

    public function testParseEntriesWithEmptyValues(): void
    {
        // Test that empty field values are handled correctly
        $adif = "Test\n<eoh>\n<call:5>W1AW<band:0><mode:3>SSB<eor>";
        $doc = new Document($adif);
        $doc->parse();

        $entries = $doc->getEntries();
        $this->assertCount(1, $entries);
        $this->assertEquals('w1aw', $entries[0]['call']);
        // Empty band should not be included
        $this->assertArrayNotHasKey('band', $entries[0]);
    }

    public function testUnrollPotaRefsDirectCallPreservesTimer(): void
    {
        // Test calling unroll_pota_refs directly
        $adif = "Test\n<eoh>\n<call:5>W1AW<my_pota_ref:7>US-0001<eor>";
        $doc = new Document($adif);
        $doc->parse();
        $doc->unroll_pota_refs();

        $timers = $doc->getTimers();
        $this->assertArrayHasKey('unroll_pota_refs', $timers);
    }

    public function testChunkTimerIsRecorded(): void
    {
        $doc = new Document();
        $doc->addEntry(['call' => 'W1AW']);
        $doc->chunk();

        $timers = $doc->getTimers();
        $this->assertArrayHasKey('chunk', $timers);
    }

    public function testMorphTimerIsRecorded(): void
    {
        $adif = "Test\n<eoh>\n<call:5>W1AW<my_pota_ref:7>US-0001<eor>";
        $doc = new Document($adif);
        $doc->parse();
        $doc->morph(Adif::MORPH_POTA_REFS);

        $timers = $doc->getTimers();
        $this->assertArrayHasKey('morph', $timers);
    }

    public function testUnrollPotaRefsHunterFerWithoutActivator(): void
    {
        // Hunter has multiple parks, no activator ref at all
        $adif = "Test\n<eoh>\n<call:5>W1AW<pota_ref:15>US-0003,US-0004<eor>";
        $doc = new Document($adif);
        $doc->parse();
        $doc->morph(Adif::MORPH_POTA_REFS);

        $this->assertEquals(2, $doc->count);

        $entries = $doc->getEntries();
        foreach ($entries as $entry) {
            $this->assertContains($entry['pota_park_ref'], ['us-0003', 'us-0004']);
            $this->assertArrayNotHasKey('pota_my_park_ref', $entry);
        }
    }

    public function testUnrollPotaRefsSigInfoWithFer(): void
    {
        // sig_info with comma-separated values (fer)
        $adif = "Test\n<eoh>\n<call:5>W1AW<my_sig_info:15>US-0001,US-0002<eor>";
        $doc = new Document($adif);
        $doc->parse();
        $doc->morph(Adif::MORPH_POTA_REFS);

        $this->assertEquals(2, $doc->count);

        $entries = $doc->getEntries();
        $parks = [];
        foreach ($entries as $entry) {
            $parks[] = $entry['pota_my_park_ref'];
        }
        $this->assertContains('us-0001', $parks);
        $this->assertContains('us-0002', $parks);
    }

    public function testUnrollPotaRefsTracksUnrolledFromRec(): void
    {
        // Verify pota_unrolled_from_rec is set when unrolling
        $adif = "Test\n<eoh>\n<call:5>W1AW<my_pota_ref:15>US-0001,US-0002<eor>";
        $doc = new Document($adif);
        $doc->parse();
        $doc->morph(Adif::MORPH_POTA_REFS);

        $entries = $doc->getEntries();
        foreach ($entries as $entry) {
            $this->assertArrayHasKey('pota_unrolled_from_rec', $entry);
        }
    }
}
