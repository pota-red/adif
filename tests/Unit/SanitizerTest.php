<?php

declare(strict_types=1);

namespace Pota\Adif\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Pota\Adif\Sanitizer;

final class SanitizerTest extends TestCase
{
    // Station callsign to operator copying
    public function test_copies_station_callsign_to_operator_when_missing(): void
    {
        $fields = ['station_callsign' => 'W1AW', 'call' => 'K1ABC'];
        $result = Sanitizer::entry($fields);

        $this->assertEquals('W1AW', $result['operator']);
    }

    public function test_does_not_override_existing_operator(): void
    {
        $fields = ['station_callsign' => 'W1AW', 'operator' => 'K1XYZ', 'call' => 'K1ABC'];
        $result = Sanitizer::entry($fields);

        $this->assertEquals('K1XYZ', $result['operator']);
    }

    // Integer field sanitization
    #[DataProvider('integerFieldProvider')]
    public function test_sanitizes_integer_fields(string $field, mixed $input, int $expected): void
    {
        $fields = [$field => $input];
        $result = Sanitizer::entry($fields);

        $this->assertSame($expected, $result[$field]);
    }

    public static function integerFieldProvider(): array
    {
        return [
            'tx_pwr string' => ['tx_pwr', '100', 100],
            'rx_pwr float' => ['rx_pwr', '5.5', 5],
            'age numeric' => ['age', '42', 42],
            'dxcc string' => ['dxcc', '291', 291],
            'ant_az string' => ['ant_az', '180', 180],
            'ant_el string' => ['ant_el', '45', 45],
            'a_index string' => ['a_index', '10', 10],
            'k_index string' => ['k_index', '3', 3],
            'sfi string' => ['sfi', '150', 150],
        ];
    }

    // Float field sanitization
    #[DataProvider('floatFieldProvider')]
    public function test_sanitizes_float_fields(string $field, mixed $input, float $expected): void
    {
        $fields = [$field => $input];
        $result = Sanitizer::entry($fields);

        $this->assertSame($expected, $result[$field]);
    }

    public static function floatFieldProvider(): array
    {
        return [
            'altitude' => ['altitude', '1234.56', 1234.56],
            'distance' => ['distance', '100', 100.0],
        ];
    }

    // Uppercase field sanitization
    #[DataProvider('uppercaseFieldProvider')]
    public function test_sanitizes_uppercase_fields(string $field, string $input, string $expected): void
    {
        $fields = [$field => $input];
        $result = Sanitizer::entry($fields);

        $this->assertEquals($expected, $result[$field]);
    }

    public static function uppercaseFieldProvider(): array
    {
        return [
            'band lowercase' => ['band', '20m', '20M'],
            'call mixed' => ['call', 'w1aw', 'W1AW'],
            'pota_ref' => ['pota_ref', 'us-0001', 'US-0001'],
            'gridsquare' => ['gridsquare', 'fn31pr', 'FN31PR'],
            'operator' => ['operator', 'w1aw', 'W1AW'],
            'station_callsign' => ['station_callsign', 'k1abc', 'K1ABC'],
            'state' => ['state', 'wa', 'WA'],
            'submode' => ['submode', 'usb', 'USB'],
            'sig' => ['sig', 'pota', 'POTA'],
            'sig_info' => ['sig_info', 'k-0001', 'K-0001'],
        ];
    }

    // Mode/submode special handling
    public function test_converts_usb_to_ssb_with_submode(): void
    {
        $fields = ['mode' => 'USB'];
        $result = Sanitizer::entry($fields);

        $this->assertEquals('SSB', $result['mode']);
        $this->assertEquals('USB', $result['submode']);
    }

    public function test_converts_lsb_to_ssb_with_submode(): void
    {
        $fields = ['mode' => 'LSB'];
        $result = Sanitizer::entry($fields);

        $this->assertEquals('SSB', $result['mode']);
        $this->assertEquals('LSB', $result['submode']);
    }

    public function test_does_not_convert_other_modes_to_ssb(): void
    {
        $fields = ['mode' => 'CW'];
        $result = Sanitizer::entry($fields);

        $this->assertEquals('CW', $result['mode']);
        $this->assertArrayNotHasKey('submode', $result);
    }

    public function test_preserves_existing_submode(): void
    {
        $fields = ['mode' => 'SSB', 'submode' => 'USB'];
        $result = Sanitizer::entry($fields);

        $this->assertEquals('SSB', $result['mode']);
        $this->assertEquals('USB', $result['submode']);
    }

    // Date field sanitization
    #[DataProvider('dateFieldProvider')]
    public function test_sanitizes_date_fields(string $field, string $input, string $expected): void
    {
        $fields = [$field => $input];
        $result = Sanitizer::entry($fields);

        $this->assertEquals($expected, $result[$field]);
    }

    public static function dateFieldProvider(): array
    {
        return [
            'qso_date with dashes' => ['qso_date', '2023-12-25', '20231225'],
            'qso_date with slashes' => ['qso_date', '2023/12/25', '20231225'],
            'qso_date clean' => ['qso_date', '20231225', '20231225'],
            'qso_date_off with dashes' => ['qso_date_off', '2023-12-25', '20231225'],
        ];
    }

    // Time field sanitization
    #[DataProvider('timeFieldProvider')]
    public function test_sanitizes_time_fields(string $field, string $input, string $expected): void
    {
        $fields = [$field => $input];
        $result = Sanitizer::entry($fields);

        $this->assertEquals($expected, $result[$field]);
    }

    public static function timeFieldProvider(): array
    {
        return [
            'time_on short' => ['time_on', '1234', '123400'],
            'time_on full' => ['time_on', '123456', '123456'],
            'time_on with colon' => ['time_on', '12:34', '123400'],
            'time_off short' => ['time_off', '1400', '140000'],
        ];
    }

    // Frequency field sanitization
    public function test_sanitizes_frequency_to_six_decimals(): void
    {
        $fields = ['freq' => '14.074'];
        $result = Sanitizer::entry($fields);

        $this->assertEquals('14.074000', $result['freq']);
    }

    public function test_sanitizes_frequency_rx_to_six_decimals(): void
    {
        $fields = ['freq_rx' => '14.1'];
        $result = Sanitizer::entry($fields);

        $this->assertEquals('14.100000', $result['freq_rx']);
    }

    public function test_handles_whole_number_frequency(): void
    {
        $fields = ['freq' => '14'];
        $result = Sanitizer::entry($fields);

        $this->assertEquals('14.000000', $result['freq']);
    }

    // Band derivation from frequency
    public function test_derives_band_from_freq_when_band_invalid(): void
    {
        $fields = ['band' => 'INVALID', 'freq' => '14.074'];
        $result = Sanitizer::entry($fields);

        $this->assertEquals('20M', $result['band']);
    }

    public function test_derives_band_rx_from_freq_rx_when_band_rx_invalid(): void
    {
        $fields = ['band_rx' => 'XX', 'freq_rx' => '7.074'];
        $result = Sanitizer::entry($fields);

        $this->assertEquals('40M', $result['band_rx']);
    }

    public function test_does_not_override_valid_band(): void
    {
        $fields = ['band' => '20M', 'freq' => '14.074'];
        $result = Sanitizer::entry($fields);

        $this->assertEquals('20M', $result['band']);
    }

    // Single character field truncation
    #[DataProvider('singleCharFieldProvider')]
    public function test_truncates_single_character_fields(string $field, string $input, string $expected): void
    {
        $fields = [$field => $input];
        $result = Sanitizer::entry($fields);

        $this->assertEquals($expected, $result[$field]);
    }

    public static function singleCharFieldProvider(): array
    {
        return [
            'qsl_rcvd multi' => ['qsl_rcvd', 'YES', 'Y'],
            'qsl_sent multi' => ['qsl_sent', 'NO', 'N'],
            'lotw_qsl_rcvd multi' => ['lotw_qsl_rcvd', 'VERIFIED', 'V'],
            'ant_path multi' => ['ant_path', 'SHORT', 'S'],
            'single char preserved' => ['qsl_rcvd', 'Y', 'Y'],
        ];
    }

    // Continent truncation
    public function test_truncates_continent_to_two_chars(): void
    {
        $fields = ['cont' => 'NORTH'];
        $result = Sanitizer::entry($fields);

        $this->assertEquals('NO', $result['cont']);
    }

    public function test_preserves_two_char_continent(): void
    {
        $fields = ['cont' => 'NA'];
        $result = Sanitizer::entry($fields);

        $this->assertEquals('NA', $result['cont']);
    }

    // Boolean field sanitization
    #[DataProvider('booleanFieldProvider')]
    public function test_sanitizes_boolean_fields(string $field, mixed $input, bool $expected): void
    {
        $fields = [$field => $input];
        $result = Sanitizer::entry($fields);

        $this->assertSame($expected, $result[$field]);
    }

    public static function booleanFieldProvider(): array
    {
        return [
            'silent_key true string' => ['silent_key', '1', true],
            'silent_key false string' => ['silent_key', '0', false],
            'qso_random true' => ['qso_random', '1', true],
            'force_init true' => ['force_init', '1', true],
        ];
    }

    // Filter method tests
    public function test_filter_removes_optional_fields_with_errors(): void
    {
        $fields = ['call' => 'W1AW', 'gridsquare' => 'INVALID', 'band' => '20M'];
        $errors = ['gridsquare'];
        $optional = ['gridsquare', 'my_gridsquare'];

        [$cleanFields, $noErrors] = Sanitizer::filter($fields, $errors, $optional);

        $this->assertArrayNotHasKey('gridsquare', $cleanFields);
        $this->assertTrue($noErrors);
    }

    public function test_filter_keeps_required_fields_with_errors(): void
    {
        $fields = ['call' => 'INVALID', 'band' => '20M'];
        $errors = ['call'];
        $optional = ['gridsquare'];

        [$cleanFields, $noErrors] = Sanitizer::filter($fields, $errors, $optional);

        $this->assertArrayHasKey('call', $cleanFields);
        $this->assertFalse($noErrors);
    }

    public function test_filter_handles_multiple_optional_errors(): void
    {
        $fields = ['call' => 'W1AW', 'gridsquare' => 'BAD', 'my_gridsquare' => 'BAD2', 'band' => '20M'];
        $errors = ['gridsquare', 'my_gridsquare'];
        $optional = ['gridsquare', 'my_gridsquare'];

        [$cleanFields, $noErrors] = Sanitizer::filter($fields, $errors, $optional);

        $this->assertArrayNotHasKey('gridsquare', $cleanFields);
        $this->assertArrayNotHasKey('my_gridsquare', $cleanFields);
        $this->assertTrue($noErrors);
    }

    // RST fields are not modified
    public function test_rst_fields_are_not_modified(): void
    {
        $fields = ['rst_sent' => '599', 'rst_rcvd' => '57'];
        $result = Sanitizer::entry($fields);

        $this->assertEquals('599', $result['rst_sent']);
        $this->assertEquals('57', $result['rst_rcvd']);
    }

    // Complete entry sanitization
    public function test_sanitizes_complete_entry(): void
    {
        $fields = [
            'band' => '20m',
            'call' => 'k1abc',
            'mode' => 'usb',
            'operator' => 'w1aw',
            'qso_date' => '2023-12-25',
            'time_on' => '1234',
            'freq' => '14.074',
            'tx_pwr' => '100',
        ];
        $result = Sanitizer::entry($fields);

        $this->assertEquals('20M', $result['band']);
        $this->assertEquals('K1ABC', $result['call']);
        $this->assertEquals('SSB', $result['mode']);
        $this->assertEquals('USB', $result['submode']);
        $this->assertEquals('W1AW', $result['operator']);
        $this->assertEquals('20231225', $result['qso_date']);
        $this->assertEquals('123400', $result['time_on']);
        $this->assertEquals('14.074000', $result['freq']);
        $this->assertSame(100, $result['tx_pwr']);
    }
}
