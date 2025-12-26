<?php

declare(strict_types=1);

namespace Pota\Adif\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Pota\Adif\Adif;
use Pota\Adif\Document;

final class DocumentTest extends TestCase
{
    // Construction tests
    public function test_constructs_from_file(): void
    {
        $path = testDataPath('p75.adi');
        $doc = new Document($path);

        $this->assertEquals('p75.adi', $doc->filename);
        $this->assertEquals(0, $doc->count); // Not parsed yet
    }

    public function test_constructs_from_string(): void
    {
        $adif = "Test header\n<eoh>\n<call:5>W1AW<band:3>20M<mode:3>SSB<eor>";
        $doc = new Document($adif);

        $this->assertEquals('', $doc->filename);
        $this->assertEquals(0, $doc->count); // Not parsed yet
    }

    public function test_constructs_empty_document(): void
    {
        $doc = new Document;
        $this->assertEquals(0, $doc->count);
    }

    public function test_throws_exception_for_invalid_file(): void
    {
        $this->expectException(\Exception::class);
        // Non-existent files throw the generic "Unable to create instance" exception
        // since the path doesn't contain <eor> and isn't a valid file
        $this->expectExceptionMessage('Unable to create instance');

        new Document('/nonexistent/file.adi');
    }

    public function test_throws_exception_for_invalid_data(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Unable to create instance');

        new Document('random string without eor');
    }

    // Parsing tests
    public function test_parses_simple_file(): void
    {
        $path = testDataPath('small.adi');
        $doc = new Document($path);
        $doc->parse();

        $this->assertGreaterThan(0, $doc->count);
    }

    public function test_parses_p75_file(): void
    {
        $path = testDataPath('p75.adi');
        $doc = new Document($path);
        $doc->parse();

        // p75.adi actually contains 50 entries
        $this->assertEquals(50, $doc->count);
    }

    public function test_parse_extracts_headers(): void
    {
        $adif = "ADIF Export\n<programid:8>Test App<programversion:5>1.0.0<eoh>\n<call:5>W1AW<eor>";
        $doc = new Document($adif);
        $doc->parse();

        $this->assertEquals('Test App', $doc->getHeaders('programid'));
        $this->assertEquals('1.0.0', $doc->getHeaders('programversion'));
    }

    public function test_parse_extracts_entries(): void
    {
        $adif = "Test\n<eoh>\n<call:5>W1AW<band:3>20M<mode:3>SSB<eor>";
        $doc = new Document($adif);
        $doc->parse();

        $entries = $doc->getEntries();
        $this->assertCount(1, $entries);
        $this->assertEquals('w1aw', $entries[0]['call']);
        $this->assertEquals('20m', $entries[0]['band']);
    }

    public function test_parse_handles_multiple_entries(): void
    {
        $adif = "Test\n<eoh>\n" .
                "<call:5>W1AW<band:3>20M<eor>\n" .
                '<call:5>K1ABC<band:3>40M<eor>';
        $doc = new Document($adif);
        $doc->parse();

        $this->assertEquals(2, $doc->count);
    }

    public function test_parse_without_header_section(): void
    {
        $adif = '<call:5>W1AW<band:3>20M<eor>';
        $doc = new Document($adif);
        $doc->parse();

        $this->assertEquals(1, $doc->count);
    }

    public function test_parse_throws_on_malformed_input(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Malformed input');

        $doc = new Document;
        $doc->fromString('no tags at all');
        $doc->parse();
    }

    // First/last entry tracking
    public function test_tracks_first_and_last_entry(): void
    {
        $adif = "Test\n<eoh>\n" .
                "<call:5>W1AW<qso_date:8>20231225<time_on:6>120000<eor>\n" .
                "<call:5>K1ABC<qso_date:8>20231225<time_on:6>100000<eor>\n" .
                '<call:5>N1XYZ<qso_date:8>20231225<time_on:6>140000<eor>';
        $doc = new Document($adif);
        $doc->parse();

        $first = $doc->getFirstEntry();
        $last = $doc->getLastEntry();

        // Parsing lowercases all values
        $this->assertEquals('k1abc', $first['call']); // 1000 is earliest
        $this->assertEquals('n1xyz', $last['call']); // 1400 is latest
    }

    public function test_returns_null_for_first_last_entry_when_empty(): void
    {
        $doc = new Document;
        $this->assertNull($doc->getFirstEntry());
        $this->assertNull($doc->getLastEntry());
    }

    // Sanitization tests
    public function test_sanitize_normalizes_data(): void
    {
        $adif = "Test\n<eoh>\n<call:5>w1aw<band:2>20m<mode:3>usb<qso_date:3>foo<time_on:3>bar<eor>";
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
    public function test_validate_detects_errors(): void
    {
        $adif = "Test\n<eoh>\n<call:5>W1AW<band:7>INVALID<mode:3>XXX<qso_date:3>foo<time_on:3>bar<eor>";
        $doc = new Document($adif);
        $doc->parse();
        $doc->sanitize();
        $doc->validate();

        $this->assertTrue($doc->hasErrors());
        $errors = $doc->getErrors();
        $this->assertNotEmpty($errors);
    }

    public function test_validate_with_pota_mode(): void
    {
        $adif = "Test\n<eoh>\n<call:5>W1AW<band:3>20M<qso_date:3>foo<time_on:3>bar<eor>";
        $doc = new Document($adif);
        $doc->setMode(Document::MODE_POTA);
        $doc->parse();
        $doc->sanitize();
        $doc->validate();

        $this->assertTrue($doc->hasErrors());
    }

    public function test_disable_qps_check(): void
    {
        // Create entries at same timestamp
        $entries = [];
        for ($i = 0; $i < 100; $i++) {
            $entries[] = '<call:5>W1AW<qso_date:8>20231225<time_on:4>1200<eor>';
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
    public function test_dedupe_removes_duplicates(): void
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

    public function test_dedupe_with_no_duplicates(): void
    {
        $adif = "Test\n<eoh>\n" .
                "<call:5>W1AW<band:3>20M<qso_date:8>20231225<time_on:4>1200<mode:3>SSB<operator:4>W1AW<pota_my_park_ref:7>US-0001<eor>\n" .
                '<call:5>K1ABC<band:3>40M<qso_date:8>20231225<time_on:4>1300<mode:3>CW<operator:4>W1AW<pota_my_park_ref:7>US-0001<eor>';
        $doc = new Document($adif);
        $doc->parse();

        $originalCount = $doc->count;
        $doc->dedupe();

        $this->assertEquals($originalCount, $doc->count);
        $this->assertFalse($doc->hasDupes());
    }

    // Morph tests
    public function test_morph_adif_strict(): void
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

    public function test_morph_pota_only(): void
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

    public function test_morph_pota_refs_unrolls_single_ref(): void
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

    public function test_morph_pota_refs_unrolls_multiple_activator_refs(): void
    {
        $adif = "Test\n<eoh>\n<call:5>W1AW<my_pota_ref:15>US-0001,US-0002<eor>";
        $doc = new Document($adif);
        $doc->parse();
        $originalCount = $doc->count;
        $doc->morph(Adif::MORPH_POTA_REFS);

        $this->assertEquals($originalCount * 2, $doc->count);
    }

    public function test_morph_pota_refs_handles_location_suffix(): void
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
    public function test_chunk_keeps_small_document_intact(): void
    {
        $path = testDataPath('p75.adi');
        $doc = new Document($path);
        $doc->parse();
        $doc->chunk();

        $json = json_decode($doc->toJson(), true);
        $this->assertEquals(1, $json['meta']['chunks']);
    }

    // Output generation tests
    public function test_to_adif_generates_valid_adif(): void
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

    public function test_to_json_generates_valid_json(): void
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

    public function test_to_json_pretty_print(): void
    {
        $adif = "Test\n<eoh>\n<call:5>W1AW<eor>";
        $doc = new Document($adif);
        $doc->parse();

        $json = $doc->toJson(true);

        $this->assertStringContainsString("\n", $json);
        $this->assertStringContainsString('    ', $json); // Indentation
    }

    // Timer tests
    public function test_records_timers_for_operations(): void
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

    public function test_add_timer_aggregates_values(): void
    {
        $doc = new Document;
        $doc->addTimer('test', 1.5);
        $doc->addTimer('test', 2.5);

        $timers = $doc->getTimers();
        $this->assertEquals(4.0, $timers['test']);
    }

    public function test_sum_timers_returns_total(): void
    {
        $path = testDataPath('small.adi');
        $doc = new Document($path);
        $doc->parse();
        $doc->sanitize();

        $total = $doc->sumTimers();
        $this->assertGreaterThan(0, $total);
    }

    // Override field tests
    public function test_override_field_applies_value(): void
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
    public function test_lint_detects_format_issues(): void
    {
        $doc = new Document;
        $doc->fromString('no eoh or eor tags');

        $errors = $doc->lint();
        $this->assertNotEmpty($errors);
    }

    public function test_lint_accepts_valid_format(): void
    {
        $adif = "Test\n<eoh>\n<call:5>W1AW<eor>";
        $doc = new Document($adif);

        $errors = $doc->lint();
        $this->assertEmpty($errors);
    }

    // Header and entry manipulation
    public function test_add_header(): void
    {
        $doc = new Document;
        $doc->addHeader('programid', 'TestApp');
        $doc->addHeader('Custom header line');

        $header = $doc->getHeaders('programid');
        $this->assertEquals('TestApp', $header);
    }

    public function test_get_specific_header(): void
    {
        $adif = "Test\n<programid:7>TestApp<eoh>\n<call:5>W1AW<eor>";
        $doc = new Document($adif);
        $doc->parse();

        $programId = $doc->getHeaders('programid');
        $this->assertEquals('TestApp', $programId);
    }

    public function test_get_non_existent_header_returns_empty_string(): void
    {
        $doc = new Document;
        $result = $doc->getHeaders('nonexistent');
        $this->assertEquals('', $result);
    }

    public function test_get_null_header_returns_empty_string(): void
    {
        $doc = new Document;
        $result = $doc->getHeaders();
        $this->assertEquals('', $result);
    }

    public function test_get_empty_string_header_returns_all_headers(): void
    {
        $doc = new Document;

        $doc->addHeader('programid', 'TestApp');
        $doc->addHeader('Custom header line');

        $result = $doc->getHeaders('');

        $this->assertEquals(['programid' => 'TestApp', 0 => 'Custom header line'], $result);
    }

    public function test_add_entry(): void
    {
        $doc = new Document;
        $doc->addEntry(['call' => 'W1AW', 'band' => '20M']);

        $this->assertEquals(1, $doc->count);
        $entries = $doc->getEntries();
        $this->assertEquals('W1AW', $entries[0]['call']);
    }

    public function test_add_multiple_entries(): void
    {
        $doc = new Document;
        $doc->addEntry([
            ['call' => 'W1AW', 'band' => '20M'],
            ['call' => 'K1ABC', 'band' => '40M'],
        ]);

        $this->assertEquals(2, $doc->count);
    }

    public function test_remove_entry(): void
    {
        $doc = new Document;
        $doc->addEntry(['call' => 'W1AW']);
        $doc->addEntry(['call' => 'K1ABC']);
        $doc->removeEntry(0);

        $this->assertEquals(1, $doc->count);
    }

    // Mode tests
    public function test_set_mode_default(): void
    {
        $this->expectNotToPerformAssertions();

        $doc = new Document;
        $doc->setMode(Document::MODE_DEFAULT);
    }

    public function test_set_mode_pota(): void
    {
        $this->expectNotToPerformAssertions();

        $doc = new Document;
        $doc->setMode(Document::MODE_POTA);
    }

    // From file/string tests
    public function test_from_file(): void
    {
        $doc = new Document;
        $doc->fromFile(testDataPath('p75.adi'));

        $this->assertEquals('p75.adi', $doc->filename);
    }

    public function test_from_file_throws_for_invalid_path(): void
    {
        $this->expectException(\Exception::class);

        $doc = new Document;
        $doc->fromFile('/nonexistent/path.adi');
    }

    public function test_from_string(): void
    {
        $doc = new Document;
        $doc->fromString('<call:5>W1AW<eor>');

        // Document should be ready for parsing
        $doc->parse();
        $this->assertEquals(1, $doc->count);
    }

    // Source tracking
    public function test_add_source(): void
    {
        $doc = new Document;
        $doc->addSource(['fn=test.adi', 'ec=10']);

        $json = json_decode($doc->toJson(), true);
        $this->assertArrayHasKey('sources', $json['meta']);
        $this->assertCount(1, $json['meta']['sources']);
    }

    // =========================================================================
    // Additional tests for improved coverage
    // =========================================================================

    // unroll_pota_refs tests - covering various branches

    public function test_unroll_pota_refs_both_sides_in_fer(): void
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

    public function test_unroll_pota_refs_both_sides_in_fer_with_locations(): void
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

    public function test_unroll_pota_refs_only_hunter_side_in_fer(): void
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

    public function test_unroll_pota_refs_only_hunter_side_in_fer_with_location(): void
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

    public function test_unroll_pota_refs_activator_fer_with_hunter_location(): void
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

    public function test_unroll_pota_refs_hunter_location_only(): void
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

    public function test_unroll_pota_refs_sig_info_to_my_pota_ref(): void
    {
        // my_sig_info should be converted to my_pota_ref when my_pota_ref is not present
        $adif = "Test\n<eoh>\n<call:5>W1AW<my_sig_info:7>US-0001<eor>";
        $doc = new Document($adif);
        $doc->parse();
        $doc->morph(Adif::MORPH_POTA_REFS);

        $entries = $doc->getEntries();
        $this->assertEquals('us-0001', $entries[0]['pota_my_park_ref']);
    }

    public function test_unroll_pota_refs_sig_info_to_pota_ref(): void
    {
        // sig_info should be converted to pota_ref when pota_ref is not present
        $adif = "Test\n<eoh>\n<call:5>W1AW<sig_info:7>US-0003<eor>";
        $doc = new Document($adif);
        $doc->parse();
        $doc->morph(Adif::MORPH_POTA_REFS);

        $entries = $doc->getEntries();
        $this->assertEquals('us-0003', $entries[0]['pota_park_ref']);
    }

    public function test_unroll_pota_refs_sig_info_not_overwriting_existing(): void
    {
        // When both my_sig_info and my_pota_ref are present, my_pota_ref takes precedence
        $adif = "Test\n<eoh>\n<call:5>W1AW<my_sig_info:7>US-9999<my_pota_ref:7>US-0001<eor>";
        $doc = new Document($adif);
        $doc->parse();
        $doc->morph(Adif::MORPH_POTA_REFS);

        $entries = $doc->getEntries();
        $this->assertEquals('us-0001', $entries[0]['pota_my_park_ref']);
    }

    public function test_unroll_pota_refs_no_pota_fields(): void
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

    public function test_unroll_pota_refs_activator_fer_with_location_no_hunter(): void
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

    public function test_chunk_splits_large_document(): void
    {
        // Create a document with enough entries to exceed chunk size
        $doc = new Document;

        // Add many entries with substantial data to ensure we exceed chunk size
        for ($i = 0; $i < 500; $i++) {
            $doc->addEntry([
                'call' => 'W' . str_pad((string) $i, 4, '0', STR_PAD_LEFT),
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

    public function test_chunk_with_empty_document(): void
    {
        $doc = new Document;
        $doc->chunk();

        $json = json_decode($doc->toJson(), true);
        // Empty document should still have entries array
        $this->assertArrayHasKey('entries', $json);
    }

    public function test_chunk_preserves_all_entries(): void
    {
        $doc = new Document;

        // Add entries
        $entryCount = 100;
        for ($i = 0; $i < $entryCount; $i++) {
            $doc->addEntry([
                'call' => 'W' . str_pad((string) $i, 4, '0', STR_PAD_LEFT),
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

    public function test_generate_adif_headers_with_multiple_sources(): void
    {
        $doc = new Document;
        $doc->addSource(['fn=file1.adi', 'ec=10']);
        $doc->addSource(['fn=file2.adi', 'ec=20']);
        $doc->addEntry(['call' => 'W1AW']);

        $adif = $doc->toAdif();

        // Should contain source lines
        $this->assertStringContainsString('Source [fn=file1.adi, ec=10]', $adif);
        $this->assertStringContainsString('Source [fn=file2.adi, ec=20]', $adif);
    }

    public function test_generate_adif_headers_with_no_sources(): void
    {
        $doc = new Document;
        $doc->addEntry(['call' => 'W1AW']);

        $adif = $doc->toAdif();

        // Should not contain Source lines
        $this->assertStringNotContainsString('Source [', $adif);
        // But should still have standard headers
        $this->assertStringContainsString('<adif_version:', $adif);
        $this->assertStringContainsString('<programid:', $adif);
    }

    // Additional edge case tests

    public function test_set_mode_with_invalid_mode(): void
    {
        $this->expectNotToPerformAssertions();

        $doc = new Document;
        $doc->setMode(999); // Invalid mode
    }

    public function test_to_json_with_duplicates_and_errors(): void
    {
        // Test that duplicates and errors are included in JSON output
        $adif = "Test\n<eoh>\n" .
                "<call:5>W1AW<band:3>20M<qso_date:8>20231225<time_on:4>1200<mode:3>SSB<operator:4>W1AW<pota_my_park_ref:7>US-0001<eor>\n" .
                '<call:5>W1AW<band:3>20M<qso_date:8>20231225<time_on:4>1200<mode:3>SSB<operator:4>W1AW<pota_my_park_ref:7>US-0001<eor>'; // Duplicate

        $doc = new Document($adif);
        $doc->parse();
        $doc->dedupe();

        $json = json_decode($doc->toJson(), true);

        $this->assertArrayHasKey('duplicates', $json);
        $this->assertNotEmpty($json['duplicates']);
    }

    public function test_to_json_with_headers(): void
    {
        $adif = "Test header\n<programid:7>TestApp<programversion:3>1.0<eoh>\n<call:5>W1AW<eor>";
        $doc = new Document($adif);
        $doc->parse();

        $json = json_decode($doc->toJson(), true);

        $this->assertArrayHasKey('headers', $json);
        $this->assertEquals('TestApp', $json['headers']['programid']);
    }

    public function test_parse_entries_with_empty_values(): void
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

    public function test_unroll_pota_refs_direct_call_preserves_timer(): void
    {
        // Test calling unroll_pota_refs directly
        $adif = "Test\n<eoh>\n<call:5>W1AW<my_pota_ref:7>US-0001<eor>";
        $doc = new Document($adif);
        $doc->parse();
        $doc->unroll_pota_refs();

        $timers = $doc->getTimers();
        $this->assertArrayHasKey('unroll_pota_refs', $timers);
    }

    public function test_chunk_timer_is_recorded(): void
    {
        $doc = new Document;
        $doc->addEntry(['call' => 'W1AW']);
        $doc->chunk();

        $timers = $doc->getTimers();
        $this->assertArrayHasKey('chunk', $timers);
    }

    public function test_morph_timer_is_recorded(): void
    {
        $adif = "Test\n<eoh>\n<call:5>W1AW<my_pota_ref:7>US-0001<eor>";
        $doc = new Document($adif);
        $doc->parse();
        $doc->morph(Adif::MORPH_POTA_REFS);

        $timers = $doc->getTimers();
        $this->assertArrayHasKey('morph', $timers);
    }

    public function test_unroll_pota_refs_hunter_fer_without_activator(): void
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

    public function test_unroll_pota_refs_sig_info_with_fer(): void
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

    public function test_unroll_pota_refs_tracks_unrolled_from_rec(): void
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
