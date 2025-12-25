<?php

declare(strict_types=1);

namespace Pota\Adif\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Pota\Adif\Validator;

final class ValidatorTest extends TestCase
{
    // Valid complete entry
    public function testValidCompleteEntry(): void
    {
        $entry = $this->getValidEntry();

        $result = Validator::entry($entry);
        $this->assertTrue($result);
    }

    // Missing required fields
    #[DataProvider('missingRequiredFieldProvider')]
    public function testDetectsMissingRequiredFields(array $entry, array $expectedMissingFields): void
    {
        $result = Validator::entry($entry);

        $this->assertIsArray($result);
        foreach ($expectedMissingFields as $field) {
            $this->assertContains($field, $result);
        }
    }

    public static function missingRequiredFieldProvider(): array
    {
        return [
            'missing band' => [
                ['call' => 'K1ABC', 'mode' => 'SSB', 'operator' => 'W1AW', 'qso_date' => '20231225', 'time_on' => '1234', 'pota_my_park_ref' => 'US-0001'],
                ['band']
            ],
            'missing call' => [
                ['band' => '20M', 'mode' => 'SSB', 'operator' => 'W1AW', 'qso_date' => '20231225', 'time_on' => '1234', 'pota_my_park_ref' => 'US-0001'],
                ['call']
            ],
            'missing mode' => [
                ['band' => '20M', 'call' => 'K1ABC', 'operator' => 'W1AW', 'qso_date' => '20231225', 'time_on' => '1234', 'pota_my_park_ref' => 'US-0001'],
                ['mode']
            ],
            'missing multiple' => [
                ['call' => 'K1ABC', 'operator' => 'W1AW'],
                ['band', 'mode', 'qso_date', 'time_on', 'pota_my_park_ref']
            ],
        ];
    }

    // Self-QSO detection (@self error)
    public function testDetectsSelfQso(): void
    {
        $entry = [
            'band' => '20M',
            'call' => 'W1AW',
            'mode' => 'SSB',
            'operator' => 'W1AW', // Same as call!
            'qso_date' => '20231225',
            'time_on' => '1234',
            'pota_my_park_ref' => 'US-0001',
        ];

        $result = Validator::entry($entry);
        $this->assertIsArray($result);
        $this->assertContains('@self', $result);
    }

    public function testAllowsDifferentCallAndOperator(): void
    {
        $entry = $this->getValidEntry();

        $result = Validator::entry($entry);
        $this->assertTrue($result);
    }

    // Age validation
    #[DataProvider('invalidAgeProvider')]
    public function testDetectsInvalidAge(mixed $age): void
    {
        $entry = $this->getValidEntry(['age' => $age]);

        $result = Validator::entry($entry);
        $this->assertIsArray($result);
        $this->assertContains('age', $result);
    }

    public static function invalidAgeProvider(): array
    {
        return [
            'negative' => [-1],
            'too high' => [121],
            'non_numeric' => ['abc'],
        ];
    }

    public function testAcceptsValidAge(): void
    {
        $entry = $this->getValidEntry(['age' => 42]);
        $result = Validator::entry($entry);
        $this->assertTrue($result);
    }

    // Antenna azimuth validation
    #[DataProvider('invalidAntAzProvider')]
    public function testDetectsInvalidAntennaAzimuth(mixed $az): void
    {
        $entry = $this->getValidEntry(['ant_az' => $az]);

        $result = Validator::entry($entry);
        $this->assertIsArray($result);
        $this->assertContains('ant_az', $result);
    }

    public static function invalidAntAzProvider(): array
    {
        return [
            'negative' => [-1],
            'too high' => [361],
            'non_numeric' => ['north'],
        ];
    }

    public function testAcceptsValidAntennaAzimuth(): void
    {
        $entry = $this->getValidEntry(['ant_az' => 180]);
        $result = Validator::entry($entry);
        $this->assertTrue($result);
    }

    // Antenna elevation validation
    #[DataProvider('invalidAntElProvider')]
    public function testDetectsInvalidAntennaElevation(mixed $el): void
    {
        $entry = $this->getValidEntry(['ant_el' => $el]);

        $result = Validator::entry($entry);
        $this->assertIsArray($result);
        $this->assertContains('ant_el', $result);
    }

    public static function invalidAntElProvider(): array
    {
        return [
            'too_low' => [-91],
            'too_high' => [91],
        ];
    }

    public function testAcceptsValidAntennaElevation(): void
    {
        $entry = $this->getValidEntry(['ant_el' => 45]);
        $result = Validator::entry($entry);
        $this->assertTrue($result);
    }

    // Band validation
    public function testDetectsInvalidBand(): void
    {
        $entry = $this->getValidEntry(['band' => 'INVALID']);

        $result = Validator::entry($entry);
        $this->assertIsArray($result);
        $this->assertContains('band', $result);
    }

    public function testAcceptsValidBand(): void
    {
        $entry = $this->getValidEntry(['band' => '40M']);
        $result = Validator::entry($entry);
        $this->assertTrue($result);
    }

    // Mode validation
    public function testDetectsInvalidMode(): void
    {
        $entry = $this->getValidEntry(['mode' => 'INVALID']);

        $result = Validator::entry($entry);
        $this->assertIsArray($result);
        $this->assertContains('mode', $result);
    }

    // Submode validation
    public function testDetectsInvalidSubmode(): void
    {
        $entry = $this->getValidEntry(['submode' => 'INVALID']);

        $result = Validator::entry($entry);
        $this->assertIsArray($result);
        $this->assertContains('submode', $result);
    }

    public function testAcceptsValidSubmode(): void
    {
        $entry = $this->getValidEntry(['mode' => 'SSB', 'submode' => 'USB']);
        $result = Validator::entry($entry);
        $this->assertTrue($result);
    }

    // Date validation
    public function testDetectsInvalidQsoDate(): void
    {
        $entry = $this->getValidEntry(['qso_date' => '20231332']);

        $result = Validator::entry($entry);
        $this->assertIsArray($result);
        $this->assertContains('qso_date', $result);
    }

    // Time validation
    public function testDetectsInvalidTimeOn(): void
    {
        $entry = $this->getValidEntry(['time_on' => '2500']);

        $result = Validator::entry($entry);
        $this->assertIsArray($result);
        $this->assertContains('time_on', $result);
    }

    // Frequency validation
    public function testDetectsInvalidFrequency(): void
    {
        $entry = $this->getValidEntry(['freq' => '999.999']);

        $result = Validator::entry($entry);
        $this->assertIsArray($result);
        $this->assertContains('freq', $result);
    }

    public function testAcceptsValidFrequency(): void
    {
        $entry = $this->getValidEntry(['freq' => '14.074']);
        $result = Validator::entry($entry);
        $this->assertTrue($result);
    }

    public function testValidatesFrequencyAgainstBand(): void
    {
        $entry = $this->getValidEntry(['band' => '20M', 'freq' => '7.074']); // 40m freq with 20m band

        $result = Validator::entry($entry);
        $this->assertIsArray($result);
        $this->assertContains('freq', $result);
    }

    // Gridsquare validation
    public function testDetectsInvalidGridsquare(): void
    {
        $entry = $this->getValidEntry(['gridsquare' => 'INVALID']);

        $result = Validator::entry($entry);
        $this->assertIsArray($result);
        $this->assertContains('gridsquare', $result);
    }

    public function testAcceptsValidGridsquare(): void
    {
        $entry = $this->getValidEntry(['gridsquare' => 'FN31PR']);
        $result = Validator::entry($entry);
        $this->assertTrue($result);
    }

    // VUCC grids validation (multiple grids)
    public function testAcceptsValidVuccGrids(): void
    {
        $entry = $this->getValidEntry(['vucc_grids' => 'FN31,FN32,FN41,FN42']);
        $result = Validator::entry($entry);
        $this->assertTrue($result);
    }

    public function testDetectsInvalidVuccGrids(): void
    {
        $entry = $this->getValidEntry(['vucc_grids' => 'FN31,INVALID,FN41']);

        $result = Validator::entry($entry);
        $this->assertIsArray($result);
        $this->assertContains('vucc_grids', $result);
    }

    // POTA reference validation
    public function testAcceptsValidPotaMyParkRef(): void
    {
        $entry = $this->getValidEntry(['pota_my_park_ref' => 'US-0001']);
        $result = Validator::entry($entry);
        $this->assertTrue($result);
    }

    public function testDetectsInvalidPotaMyParkRef(): void
    {
        $entry = $this->getValidEntry(['pota_my_park_ref' => 'US-001']); // Too few digits

        $result = Validator::entry($entry);
        $this->assertIsArray($result);
        $this->assertContains('pota_my_park_ref', $result);
    }

    public function testAcceptsValidPotaParkRef(): void
    {
        $entry = $this->getValidEntry(['pota_park_ref' => 'CA-0123']);
        $result = Validator::entry($entry);
        $this->assertTrue($result);
    }

    // SOTA reference validation
    public function testAcceptsValidSotaRef(): void
    {
        $entry = $this->getValidEntry(['sota_ref' => 'W7W/LC-001']);
        $result = Validator::entry($entry);
        $this->assertTrue($result);
    }

    public function testDetectsInvalidSotaRef(): void
    {
        $entry = $this->getValidEntry(['sota_ref' => 'INVALID']);

        $result = Validator::entry($entry);
        $this->assertIsArray($result);
        $this->assertContains('sota_ref', $result);
    }

    // IOTA reference validation
    public function testAcceptsValidIotaRef(): void
    {
        $entry = $this->getValidEntry(['iota' => 'NA-001']);
        $result = Validator::entry($entry);
        $this->assertTrue($result);
    }

    public function testDetectsInvalidIotaRef(): void
    {
        $entry = $this->getValidEntry(['iota' => 'INVALID']);

        $result = Validator::entry($entry);
        $this->assertIsArray($result);
        $this->assertContains('iota', $result);
    }

    // WWFF reference validation
    public function testAcceptsValidWwffRef(): void
    {
        $entry = $this->getValidEntry(['wwff_ref' => 'USFF-0001']);
        $result = Validator::entry($entry);
        $this->assertTrue($result);
    }

    public function testDetectsInvalidWwffRef(): void
    {
        $entry = $this->getValidEntry(['wwff_ref' => 'INVALID']);

        $result = Validator::entry($entry);
        $this->assertIsArray($result);
        $this->assertContains('wwff_ref', $result);
    }

    // K-index validation
    #[DataProvider('invalidKIndexProvider')]
    public function testDetectsInvalidKIndex(mixed $k): void
    {
        $entry = $this->getValidEntry(['k_index' => $k]);

        $result = Validator::entry($entry);
        $this->assertIsArray($result);
        $this->assertContains('k_index', $result);
    }

    public static function invalidKIndexProvider(): array
    {
        return [
            'negative' => [-1],
            'too_high' => [10],
        ];
    }

    public function testAcceptsValidKIndex(): void
    {
        $entry = $this->getValidEntry(['k_index' => 5]);
        $result = Validator::entry($entry);
        $this->assertTrue($result);
    }

    // SFI validation
    #[DataProvider('invalidSfiProvider')]
    public function testDetectsInvalidSfi(mixed $sfi): void
    {
        $entry = $this->getValidEntry(['sfi' => $sfi]);

        $result = Validator::entry($entry);
        $this->assertIsArray($result);
        $this->assertContains('sfi', $result);
    }

    public static function invalidSfiProvider(): array
    {
        return [
            'negative' => [-1],
            'too_high' => [301],
        ];
    }

    public function testAcceptsValidSfi(): void
    {
        $entry = $this->getValidEntry(['sfi' => 150]);
        $result = Validator::entry($entry);
        $this->assertTrue($result);
    }

    // A-index validation
    public function testDetectsInvalidAIndex(): void
    {
        $entry = $this->getValidEntry(['a_index' => 401]);

        $result = Validator::entry($entry);
        $this->assertIsArray($result);
        $this->assertContains('a_index', $result);
    }

    public function testAcceptsValidAIndex(): void
    {
        $entry = $this->getValidEntry(['a_index' => 10]);
        $result = Validator::entry($entry);
        $this->assertTrue($result);
    }

    // DXCC validation
    public function testDetectsInvalidDxcc(): void
    {
        $entry = $this->getValidEntry(['dxcc' => '9999']);

        $result = Validator::entry($entry);
        $this->assertIsArray($result);
        $this->assertContains('dxcc', $result);
    }

    public function testAcceptsValidDxcc(): void
    {
        $entry = $this->getValidEntry(['dxcc' => '291']);
        $result = Validator::entry($entry);
        $this->assertTrue($result);
    }

    // Continent validation
    public function testDetectsInvalidContinent(): void
    {
        $entry = $this->getValidEntry(['cont' => 'XX']);

        $result = Validator::entry($entry);
        $this->assertIsArray($result);
        $this->assertContains('cont', $result);
    }

    public function testAcceptsValidContinent(): void
    {
        $entry = $this->getValidEntry(['cont' => 'NA']);
        $result = Validator::entry($entry);
        $this->assertTrue($result);
    }

    // Propagation mode validation
    public function testDetectsInvalidPropMode(): void
    {
        $entry = $this->getValidEntry(['prop_mode' => 'INVALID']);

        $result = Validator::entry($entry);
        $this->assertIsArray($result);
        $this->assertContains('prop_mode', $result);
    }

    public function testAcceptsValidPropMode(): void
    {
        $entry = $this->getValidEntry(['prop_mode' => 'ES']);
        $result = Validator::entry($entry);
        $this->assertTrue($result);
    }

    // QSO status validations
    public function testDetectsInvalidQsoUploadStatus(): void
    {
        $entry = $this->getValidEntry(['clublog_qso_upload_status' => 'X']);

        $result = Validator::entry($entry);
        $this->assertIsArray($result);
        $this->assertContains('clublog_qso_upload_status', $result);
    }

    public function testDetectsInvalidQsoDownloadStatus(): void
    {
        $entry = $this->getValidEntry(['qrzcom_qso_download_status' => 'X']);

        $result = Validator::entry($entry);
        $this->assertIsArray($result);
        $this->assertContains('qrzcom_qso_download_status', $result);
    }

    public function testDetectsInvalidQslRcvd(): void
    {
        $entry = $this->getValidEntry(['qsl_rcvd' => 'X']);

        $result = Validator::entry($entry);
        $this->assertIsArray($result);
        $this->assertContains('qsl_rcvd', $result);
    }

    public function testDetectsInvalidQslSent(): void
    {
        $entry = $this->getValidEntry(['qsl_sent' => 'X']);

        $result = Validator::entry($entry);
        $this->assertIsArray($result);
        $this->assertContains('qsl_sent', $result);
    }

    public function testDetectsInvalidQslVia(): void
    {
        $entry = $this->getValidEntry(['qsl_rcvd_via' => 'X']);

        $result = Validator::entry($entry);
        $this->assertIsArray($result);
        $this->assertContains('qsl_rcvd_via', $result);
    }

    // Power/distance validations
    public function testDetectsNegativeTxPwr(): void
    {
        $entry = $this->getValidEntry(['tx_pwr' => -10]);

        $result = Validator::entry($entry);
        $this->assertIsArray($result);
        $this->assertContains('tx_pwr', $result);
    }

    public function testDetectsNegativeDistance(): void
    {
        $entry = $this->getValidEntry(['distance' => -100]);

        $result = Validator::entry($entry);
        $this->assertIsArray($result);
        $this->assertContains('distance', $result);
    }

    // CQ Zone validation
    public function testDetectsInvalidCqZone(): void
    {
        $entry = $this->getValidEntry(['my_cq_zone' => 50]);

        $result = Validator::entry($entry);
        $this->assertIsArray($result);
        $this->assertContains('my_cq_zone', $result);
    }

    public function testAcceptsValidCqZone(): void
    {
        $entry = $this->getValidEntry(['my_cq_zone' => 3]);
        $result = Validator::entry($entry);
        $this->assertTrue($result);
    }

    // ITU Zone validation
    public function testDetectsInvalidItuZone(): void
    {
        $entry = $this->getValidEntry(['my_itu_zone' => 100]);

        $result = Validator::entry($entry);
        $this->assertIsArray($result);
        $this->assertContains('my_itu_zone', $result);
    }

    // Null field handling
    public function testRemovesNullFields(): void
    {
        $entry = $this->getValidEntry(['optional_field' => null]);
        $result = Validator::entry($entry);
        $this->assertTrue($result);
    }

    // Duration validation tests
    public function testDurationValidationAcceptsReasonableQsoRate(): void
    {
        $entries = [
            ['qso_date' => '20231225', 'time_on' => '120000'],
            ['qso_date' => '20231225', 'time_on' => '120500'],
            ['qso_date' => '20231225', 'time_on' => '121000'],
        ];

        $result = Validator::duration($entries);
        $this->assertTrue($result);
    }

    public function testDurationValidationRejectsUnrealisticQsoRate(): void
    {
        // 10 QSOs at same timestamp = infinite QPS > 5 QPS limit
        $entries = [];
        for ($i = 0; $i < 10; $i++) {
            $entries[] = ['qso_date' => '20231225', 'time_on' => '120000'];
        }

        $result = Validator::duration($entries);
        $this->assertFalse($result);
    }

    public function testDurationValidationWithSpreadOutQsos(): void
    {
        $entries = [];
        for ($i = 0; $i < 50; $i++) {
            $time = sprintf('%02d%02d00', 12 + intval($i / 60), $i % 60);
            $entries[] = ['qso_date' => '20231225', 'time_on' => $time];
        }

        $result = Validator::duration($entries);
        $this->assertTrue($result);
    }

    // Antenna path validation (covering lines 44-48)
    public function testDetectsInvalidAntPath(): void
    {
        $entry = $this->getValidEntry(['ant_path' => 'INVALID']);

        $result = Validator::entry($entry);
        $this->assertIsArray($result);
        $this->assertContains('ant_path', $result);
    }

    public function testAcceptsValidAntPath(): void
    {
        $entry = $this->getValidEntry(['ant_path' => 'S']); // Short path
        $result = Validator::entry($entry);
        $this->assertTrue($result);
    }

    #[DataProvider('validAntPathProvider')]
    public function testAcceptsAllValidAntPaths(string $path): void
    {
        $entry = $this->getValidEntry(['ant_path' => $path]);
        $result = Validator::entry($entry);
        $this->assertTrue($result);
    }

    public static function validAntPathProvider(): array
    {
        return [
            'grayline' => ['G'],
            'other' => ['O'],
            'short path' => ['S'],
            'long path' => ['L'],
        ];
    }

    // ARRL Section validation (covering lines 49-54)
    public function testDetectsInvalidArrlSection(): void
    {
        $entry = $this->getValidEntry(['arrl_sect' => 'INVALID']);

        $result = Validator::entry($entry);
        $this->assertIsArray($result);
        $this->assertContains('arrl_sect', $result);
    }

    public function testDetectsInvalidMyArrlSection(): void
    {
        $entry = $this->getValidEntry(['my_arrl_sect' => 'INVALID']);

        $result = Validator::entry($entry);
        $this->assertIsArray($result);
        $this->assertContains('my_arrl_sect', $result);
    }

    public function testAcceptsValidArrlSection(): void
    {
        $entry = $this->getValidEntry(['arrl_sect' => 'CT']); // Connecticut
        $result = Validator::entry($entry);
        $this->assertTrue($result);
    }

    public function testAcceptsValidMyArrlSection(): void
    {
        $entry = $this->getValidEntry(['my_arrl_sect' => 'ENY']); // Eastern New York
        $result = Validator::entry($entry);
        $this->assertTrue($result);
    }

    // Altitude validation (covering lines 74-79)
    #[DataProvider('invalidAltitudeProvider')]
    public function testDetectsInvalidAltitude(mixed $altitude): void
    {
        $entry = $this->getValidEntry(['altitude' => $altitude]);

        $result = Validator::entry($entry);
        $this->assertIsArray($result);
        $this->assertContains('altitude', $result);
    }

    #[DataProvider('invalidAltitudeProvider')]
    public function testDetectsInvalidMyAltitude(mixed $altitude): void
    {
        $entry = $this->getValidEntry(['my_altitude' => $altitude]);

        $result = Validator::entry($entry);
        $this->assertIsArray($result);
        $this->assertContains('my_altitude', $result);
    }

    public static function invalidAltitudeProvider(): array
    {
        return [
            'non_numeric_text' => ['abc'],
            'non_numeric_mixed' => ['100m'],
        ];
    }

    public function testAcceptsValidAltitude(): void
    {
        $entry = $this->getValidEntry(['altitude' => 1000]);
        $result = Validator::entry($entry);
        $this->assertTrue($result);
    }

    public function testAcceptsValidMyAltitude(): void
    {
        $entry = $this->getValidEntry(['my_altitude' => -50]); // Below sea level is valid
        $result = Validator::entry($entry);
        $this->assertTrue($result);
    }

    public function testAcceptsNegativeAltitude(): void
    {
        $entry = $this->getValidEntry(['altitude' => -100]); // Death Valley, etc.
        $result = Validator::entry($entry);
        $this->assertTrue($result);
    }

    // NR Bursts/Pings validation (covering lines 90-95)
    #[DataProvider('invalidNrBurstsProvider')]
    public function testDetectsInvalidNrBursts(mixed $value): void
    {
        $entry = $this->getValidEntry(['nr_bursts' => $value]);

        $result = Validator::entry($entry);
        $this->assertIsArray($result);
        $this->assertContains('nr_bursts', $result);
    }

    #[DataProvider('invalidNrBurstsProvider')]
    public function testDetectsInvalidNrPings(mixed $value): void
    {
        $entry = $this->getValidEntry(['nr_pings' => $value]);

        $result = Validator::entry($entry);
        $this->assertIsArray($result);
        $this->assertContains('nr_pings', $result);
    }

    public static function invalidNrBurstsProvider(): array
    {
        return [
            'float' => [3.14],
            'string_number' => ['5'],
            'string_text' => ['five'],
        ];
    }

    public function testAcceptsValidNrBursts(): void
    {
        $entry = $this->getValidEntry(['nr_bursts' => 5]);
        $result = Validator::entry($entry);
        $this->assertTrue($result);
    }

    public function testAcceptsValidNrPings(): void
    {
        $entry = $this->getValidEntry(['nr_pings' => 10]);
        $result = Validator::entry($entry);
        $this->assertTrue($result);
    }

    // Freq RX validation with band_rx (covering lines 111-115)
    public function testDetectsInvalidFreqRx(): void
    {
        $entry = $this->getValidEntry(['freq_rx' => '999.999']);

        $result = Validator::entry($entry);
        $this->assertIsArray($result);
        $this->assertContains('freq_rx', $result);
    }

    public function testDetectsFreqRxBandRxMismatch(): void
    {
        $entry = $this->getValidEntry([
            'band_rx' => '20M',
            'freq_rx' => '7.074', // 40m freq with 20m band_rx
        ]);

        $result = Validator::entry($entry);
        $this->assertIsArray($result);
        $this->assertContains('freq_rx', $result);
    }

    public function testAcceptsValidFreqRxWithBandRx(): void
    {
        $entry = $this->getValidEntry([
            'band_rx' => '40M',
            'freq_rx' => '7.074',
        ]);
        $result = Validator::entry($entry);
        $this->assertTrue($result);
    }

    public function testAcceptsValidFreqRxWithoutBandRx(): void
    {
        $entry = $this->getValidEntry(['freq_rx' => '14.074']);
        $result = Validator::entry($entry);
        $this->assertTrue($result);
    }

    // Band RX validation (covering lines 116-121)
    public function testDetectsInvalidBandRx(): void
    {
        $entry = $this->getValidEntry(['band_rx' => 'INVALID']);

        $result = Validator::entry($entry);
        $this->assertIsArray($result);
        $this->assertContains('band_rx', $result);
    }

    public function testAcceptsValidBandRx(): void
    {
        $entry = $this->getValidEntry(['band_rx' => '40M']);
        $result = Validator::entry($entry);
        $this->assertTrue($result);
    }

    // My WWFF Ref validation (covering line 253)
    public function testDetectsInvalidMyWwffRef(): void
    {
        $entry = $this->getValidEntry(['my_wwff_ref' => 'INVALID']);

        $result = Validator::entry($entry);
        $this->assertIsArray($result);
        $this->assertContains('my_wwff_ref', $result);
    }

    public function testAcceptsValidMyWwffRef(): void
    {
        $entry = $this->getValidEntry(['my_wwff_ref' => 'USFF-0001']);
        $result = Validator::entry($entry);
        $this->assertTrue($result);
    }

    // My SOTA Ref validation
    public function testDetectsInvalidMySotaRef(): void
    {
        $entry = $this->getValidEntry(['my_sota_ref' => 'INVALID']);

        $result = Validator::entry($entry);
        $this->assertIsArray($result);
        $this->assertContains('my_sota_ref', $result);
    }

    public function testAcceptsValidMySotaRef(): void
    {
        $entry = $this->getValidEntry(['my_sota_ref' => 'W7W/LC-001']);
        $result = Validator::entry($entry);
        $this->assertTrue($result);
    }

    // My IOTA Ref validation
    public function testDetectsInvalidMyIotaRef(): void
    {
        $entry = $this->getValidEntry(['my_iota' => 'INVALID']);

        $result = Validator::entry($entry);
        $this->assertIsArray($result);
        $this->assertContains('my_iota', $result);
    }

    public function testAcceptsValidMyIotaRef(): void
    {
        $entry = $this->getValidEntry(['my_iota' => 'NA-001']);
        $result = Validator::entry($entry);
        $this->assertTrue($result);
    }

    // My Gridsquare validation
    public function testDetectsInvalidMyGridsquare(): void
    {
        $entry = $this->getValidEntry(['my_gridsquare' => 'INVALID']);

        $result = Validator::entry($entry);
        $this->assertIsArray($result);
        $this->assertContains('my_gridsquare', $result);
    }

    public function testAcceptsValidMyGridsquare(): void
    {
        $entry = $this->getValidEntry(['my_gridsquare' => 'FN31PR']);
        $result = Validator::entry($entry);
        $this->assertTrue($result);
    }

    // My VUCC Grids validation
    public function testDetectsInvalidMyVuccGrids(): void
    {
        $entry = $this->getValidEntry(['my_vucc_grids' => 'FN31,INVALID,FN41']);

        $result = Validator::entry($entry);
        $this->assertIsArray($result);
        $this->assertContains('my_vucc_grids', $result);
    }

    public function testAcceptsValidMyVuccGrids(): void
    {
        $entry = $this->getValidEntry(['my_vucc_grids' => 'FN31,FN32']);
        $result = Validator::entry($entry);
        $this->assertTrue($result);
    }

    // My DXCC validation
    public function testDetectsInvalidMyDxcc(): void
    {
        $entry = $this->getValidEntry(['my_dxcc' => '9999']);

        $result = Validator::entry($entry);
        $this->assertIsArray($result);
        $this->assertContains('my_dxcc', $result);
    }

    public function testAcceptsValidMyDxcc(): void
    {
        $entry = $this->getValidEntry(['my_dxcc' => '291']);
        $result = Validator::entry($entry);
        $this->assertTrue($result);
    }

    // Additional QSO upload status fields (covering all upload status variants)
    #[DataProvider('invalidQsoUploadStatusFieldsProvider')]
    public function testDetectsInvalidQsoUploadStatusVariants(string $field): void
    {
        $entry = $this->getValidEntry([$field => 'X']);

        $result = Validator::entry($entry);
        $this->assertIsArray($result);
        $this->assertContains($field, $result);
    }

    public static function invalidQsoUploadStatusFieldsProvider(): array
    {
        return [
            'hamlogeu_qso_upload_status' => ['hamlogeu_qso_upload_status'],
            'hamqth_qso_upload_status' => ['hamqth_qso_upload_status'],
            'hrdlog_qso_upload_status' => ['hrdlog_qso_upload_status'],
            'qrzcom_qso_upload_status' => ['qrzcom_qso_upload_status'],
        ];
    }

    // Additional QSL received fields
    #[DataProvider('invalidQslRcvdFieldsProvider')]
    public function testDetectsInvalidQslRcvdVariants(string $field): void
    {
        $entry = $this->getValidEntry([$field => 'X']);

        $result = Validator::entry($entry);
        $this->assertIsArray($result);
        $this->assertContains($field, $result);
    }

    public static function invalidQslRcvdFieldsProvider(): array
    {
        return [
            'dcl_qsl_rcvd' => ['dcl_qsl_rcvd'],
            'eqsl_qsl_rcvd' => ['eqsl_qsl_rcvd'],
            'lotw_qsl_rcvd' => ['lotw_qsl_rcvd'],
        ];
    }

    // Additional QSL sent fields
    #[DataProvider('invalidQslSentFieldsProvider')]
    public function testDetectsInvalidQslSentVariants(string $field): void
    {
        $entry = $this->getValidEntry([$field => 'X']);

        $result = Validator::entry($entry);
        $this->assertIsArray($result);
        $this->assertContains($field, $result);
    }

    public static function invalidQslSentFieldsProvider(): array
    {
        return [
            'dcl_qsl_sent' => ['dcl_qsl_sent'],
            'eqsl_qsl_sent' => ['eqsl_qsl_sent'],
            'lotw_qsl_sent' => ['lotw_qsl_sent'],
        ];
    }

    // QSL Sent Via validation
    public function testDetectsInvalidQslSentVia(): void
    {
        $entry = $this->getValidEntry(['qsl_sent_via' => 'X']);

        $result = Validator::entry($entry);
        $this->assertIsArray($result);
        $this->assertContains('qsl_sent_via', $result);
    }

    // Additional date fields (covering all date validation paths)
    #[DataProvider('invalidDateFieldsProvider')]
    public function testDetectsInvalidDateVariants(string $field): void
    {
        $entry = $this->getValidEntry([$field => '20231332']);

        $result = Validator::entry($entry);
        $this->assertIsArray($result);
        $this->assertContains($field, $result);
    }

    public static function invalidDateFieldsProvider(): array
    {
        return [
            'dcl_qslrdate' => ['dcl_qslrdate'],
            'dcl_qslsdate' => ['dcl_qslsdate'],
            'eqsl_qslrdate' => ['eqsl_qslrdate'],
            'eqsl_qslsdate' => ['eqsl_qslsdate'],
            'hamlogeu_qso_upload_date' => ['hamlogeu_qso_upload_date'],
            'hamqth_qso_upload_date' => ['hamqth_qso_upload_date'],
            'hrdlog_qso_upload_date' => ['hrdlog_qso_upload_date'],
            'lotw_qslrdate' => ['lotw_qslrdate'],
            'lotw_qslsdate' => ['lotw_qslsdate'],
            'qrzcom_qso_download_date' => ['qrzcom_qso_download_date'],
            'qrzcom_qso_upload_date' => ['qrzcom_qso_upload_date'],
            'qslrdate' => ['qslrdate'],
            'qslsdate' => ['qslsdate'],
            'qso_date_off' => ['qso_date_off'],
        ];
    }

    // Time off validation
    public function testDetectsInvalidTimeOff(): void
    {
        $entry = $this->getValidEntry(['time_off' => '2500']);

        $result = Validator::entry($entry);
        $this->assertIsArray($result);
        $this->assertContains('time_off', $result);
    }

    public function testAcceptsValidTimeOff(): void
    {
        $entry = $this->getValidEntry(['time_off' => '1300']);
        $result = Validator::entry($entry);
        $this->assertTrue($result);
    }

    // Additional numeric fields that must be non-negative
    #[DataProvider('nonNegativeNumericFieldsProvider')]
    public function testDetectsNegativeValuesForNonNegativeFields(string $field): void
    {
        $entry = $this->getValidEntry([$field => -1]);

        $result = Validator::entry($entry);
        $this->assertIsArray($result);
        $this->assertContains($field, $result);
    }

    #[DataProvider('nonNegativeNumericFieldsProvider')]
    public function testDetectsNonNumericValuesForNonNegativeFields(string $field): void
    {
        $entry = $this->getValidEntry([$field => 'abc']);

        $result = Validator::entry($entry);
        $this->assertIsArray($result);
        $this->assertContains($field, $result);
    }

    public static function nonNegativeNumericFieldsProvider(): array
    {
        return [
            'max_bursts' => ['max_bursts'],
            'rx_pwr' => ['rx_pwr'],
            'srx' => ['srx'],
            'stx' => ['stx'],
            'my_fists' => ['my_fists'],
            'my_iota_island_id' => ['my_iota_island_id'],
            'ten_ten' => ['ten_ten'],
            'uksmg' => ['uksmg'],
        ];
    }

    // Valid values for non-negative fields
    #[DataProvider('nonNegativeNumericFieldsProvider')]
    public function testAcceptsValidValuesForNonNegativeFields(string $field): void
    {
        $entry = $this->getValidEntry([$field => 100]);
        $result = Validator::entry($entry);
        $this->assertTrue($result);
    }

    // POTA Park Ref (not my_park_ref) invalid test
    public function testDetectsInvalidPotaParkRef(): void
    {
        $entry = $this->getValidEntry(['pota_park_ref' => 'US-001']); // Too few digits

        $result = Validator::entry($entry);
        $this->assertIsArray($result);
        $this->assertContains('pota_park_ref', $result);
    }

    // Duration with entries that trigger the else-if branch (stamp > last)
    public function testDurationWithEntriesInAscendingOrder(): void
    {
        // Entries in ascending order should trigger the else-if branch
        // where $stamp > $last is checked
        $entries = [
            ['qso_date' => '20231225', 'time_on' => '120000'],
            ['qso_date' => '20231225', 'time_on' => '130000'],
            ['qso_date' => '20231225', 'time_on' => '140000'],
            ['qso_date' => '20231225', 'time_on' => '150000'],
            ['qso_date' => '20231225', 'time_on' => '160000'],
        ];

        $result = Validator::duration($entries);
        $this->assertTrue($result);
    }

    // Duration with only one entry (edge case)
    public function testDurationWithSingleEntry(): void
    {
        $entries = [
            ['qso_date' => '20231225', 'time_on' => '120000'],
        ];

        $result = Validator::duration($entries);
        // With only one entry, diff will be 0 and count/diff will be undefined behavior
        // The code returns ($diff > 0 && ...) so this should be false
        $this->assertFalse($result);
    }

    // Test with uppercase field names (case insensitivity)
    public function testHandlesUppercaseFieldNames(): void
    {
        $entry = [
            'BAND' => '20M',
            'CALL' => 'K1ABC',
            'MODE' => 'SSB',
            'OPERATOR' => 'W1AW',
            'QSO_DATE' => '20231225',
            'TIME_ON' => '1234',
            'POTA_MY_PARK_REF' => 'US-0001',
        ];

        $result = Validator::entry($entry);
        $this->assertTrue($result);
    }

    // Test with mixed case field names
    public function testHandlesMixedCaseFieldNames(): void
    {
        $entry = [
            'Band' => '20M',
            'Call' => 'K1ABC',
            'Mode' => 'SSB',
            'Operator' => 'W1AW',
            'Qso_Date' => '20231225',
            'Time_On' => '1234',
            'Pota_My_Park_Ref' => 'US-0001',
        ];

        $result = Validator::entry($entry);
        $this->assertTrue($result);
    }

    // Test that field names with whitespace are handled by the key processing
    // The Validator trims and lowercases field names, so ' band ' becomes 'band'
    public function testHandlesFieldNamesWithWhitespace(): void
    {
        $entry = [
            ' band ' => '20M',
            'call' => 'K1ABC',
            'mode' => 'SSB',
            'operator' => 'W1AW',
            'qso_date' => '20231225',
            'time_on' => '1234',
            'pota_my_park_ref' => 'US-0001',
        ];

        $result = Validator::entry($entry);
        // The trim(strtolower($k)) in the validator normalizes ' band ' to 'band'
        // so this should pass validation
        $this->assertTrue($result);
    }

    // Helper method
    private function getValidEntry(array $overrides = []): array
    {
        $base = [
            'band' => '20M',
            'call' => 'K1ABC',
            'mode' => 'SSB',
            'operator' => 'W1AW',
            'qso_date' => '20231225',
            'time_on' => '1234',
            'pota_my_park_ref' => 'US-0001',
        ];

        return array_merge($base, $overrides);
    }
}
