<?php

namespace Pota\Adif;

class Document
{
    public const int MODE_DEFAULT = 1;

    public const int MODE_POTA = 2;

    protected int $size = 0;

    public int $count = 0;

    protected string $path = '';

    public string $filename = '';

    protected array $headers = [];

    protected array $entries = [];

    protected array $duplicates = [];

    protected array $errors = [];

    private array $timers = [];

    private string $raw = '';

    private array $sources = [];

    private array $chunks = [];

    private int $first_entry = -1;

    private int $last_entry = -1;

    private array $overrides = [];

    private int $mode = self::MODE_DEFAULT;

    private bool $check_qps = true;

    public function __construct(?string $data = null)
    {
        if (! empty($data)) {
            if (str_contains($data, '<eor>') || str_contains($data, '<EOR>')) {
                $this->fromString($data);
            } elseif (is_file($data)) {
                $this->fromFile($data);
            } else {
                throw new \Exception('Unable to create instance of ' . __CLASS__);
            }
        }
    }

    public function setMode(int $mode): void
    {
        if ($mode == self::MODE_DEFAULT || $mode == self::MODE_POTA) {
            $this->mode = $mode;
        }
    }

    public function checkQps(bool $bool): void
    {
        $this->check_qps = $bool;
    }

    public function overrideField(string $field, string $value): void
    {
        $field = trim(strtolower($field));
        $this->overrides[$field] = trim($value);
    }

    private function tick(): int|float
    {
        return hrtime(true);
    }

    private function timer(int|float $start, string $name): void
    {
        $name = trim(strtolower($name));
        $this->timers[$name] = round(($this->tick() - $start) / 1e+6, 3);
    }

    public function getTimers(?string $name = null): array|string
    {
        if (! is_null($name)) {
            $name = trim(strtolower($name));
            if (! empty($name) && array_key_exists($name, $this->timers)) {
                return $this->timers[$name];
            }
        }
        $this->timers['total'] = $this->sumTimers();

        return $this->timers;
    }

    public function addTimer(string $name, float $value): void
    {
        $name = trim(strtolower($name));
        if (! empty($name) && array_key_exists($name, $this->timers)) {
            $this->timers[$name] += $value;
        } else {
            $this->timers[$name] = $value;
        }
    }

    public function sumTimers(): float
    {
        $sum = 0;
        foreach ($this->timers as $value) {
            $sum += $value;
        }

        return round($sum, 3);
    }

    public function fromFile(string $path): void
    {
        if (is_file($path) && is_readable($path)) {
            $this->raw = file_get_contents($path);
            $this->path = dirname($path);
            $this->filename = basename($path);
            $this->size = filesize($path);
        } else {
            throw new \Exception('Invalid or unreadable file');
        }
    }

    public function fromString(string $text): void
    {
        $this->raw = $text;
        $this->size = strlen($text);
    }

    public function lint(): array
    {
        $tick = $this->tick();
        $result = Linter::lint($this->raw, $this->mode);
        $this->timer($tick, __FUNCTION__);

        return $result;
    }

    public function parse(): void
    {
        $tick = $this->tick();
        if (preg_match('/<eoh>/i', $this->raw)) {
            [$h, $d] = preg_split('/<eoh>/i', $this->raw, 2);
            $this->headers = $this->parseHeaders($h);
            $this->entries = $this->parseEntries($d);
        } elseif (preg_match('/<eor>/i', $this->raw) && str_starts_with(trim($this->raw), '<')) {
            $this->entries = $this->parseEntries($this->raw);
        } else {
            throw new \Exception('Malformed input');
        }
        $this->timer($tick, __FUNCTION__);
    }

    // TODO: currently treating all header lines as strings for some reason
    public function parseHeaders(string $text): array
    {
        $data = [];
        if (preg_match_all('/<([a-z_]*):.+?>([a-z0-9_ -.\/:@]+)?|(.*)/i', $text, $hs)) {
            foreach ($hs[3] as $h) {
                if (! empty($h)) {
                    $data['strings'][] = trim($h);
                }
            }
            for ($i = 0; $i < count($hs[1]); $i++) {
                if (! empty($hs[1][$i])) {
                    $data[strtolower($hs[1][$i])] = trim($hs[2][$i]);
                }
            }
        }

        return $data;
    }

    public function parseEntries(string $text): array
    {
        $first = time();
        $last = 0;
        $data = [];
        $text = explode('<eor>', strtolower($text));
        foreach ($text as $chunk) {
            $chunk = trim($chunk);
            if (preg_match_all('/<([a-z0-9_]*):.+?>([a-z0-9_ -.\/:@]+)?/i', $chunk, $fs)) {
                $vs = [];
                for ($i = 0; $i < count($fs[0]); $i++) {
                    $f = $fs[1][$i];
                    $v = trim($fs[2][$i]);
                    if (! empty($v)) {
                        $vs[$f] = $v;
                    }
                }
                if (! empty($vs)) {
                    ksort($vs);
                    foreach ($this->overrides as $f => $v) {
                        $vs[$f] = $v;
                    }
                    $data[] = $vs;
                }
                if (array_key_exists('qso_date', $vs) && array_key_exists('time_on', $vs)) {
                    $t = strtotime($vs['qso_date'] . ' ' . $vs['time_on']);
                    if ($t < $first) {
                        $first = $t;
                        $this->first_entry = count($data) - 1;
                    }
                    if ($t > $last) {
                        $last = $t;
                        $this->last_entry = count($data) - 1;
                    }
                }
                unset($vs);
            }
        }
        $this->count = count($data);

        return $data;
    }

    public function addSource(array $properties): void
    {
        $this->sources[] = $properties;
    }

    public function addHeader(string ...$parts): void
    {
        if (count($parts) == 1) {
            $this->headers[] = trim($parts[0]);
        } elseif (count($parts) == 2) {
            $this->headers[trim(strtolower($parts[0]))] = trim($parts[1]);
        }
    }

    public function getHeaders(?string $header = null): string|array
    {
        if (is_null($header)) {
            return '';
        }

        $header = trim(strtolower($header));
        if (! empty($header)) {
            if (array_key_exists($header, $this->headers)) {
                return $this->headers[$header];
            }

            return '';
        }

        return $this->headers;
    }

    public function addEntry(array $data): void
    {
        $ks = array_keys($data);
        if (is_numeric(array_shift($ks)) && is_numeric(array_pop($ks))) {
            foreach ($data as $entry) {
                $this->entries[] = $entry;
            }
        } else {
            $this->entries[] = $data;
        }
        $this->count = count($this->entries);
    }

    public function removeEntry(int $key): void
    {
        unset($this->entries[$key]);
        $this->count = count($this->entries);
    }

    public function getEntries(): array
    {
        return $this->entries;
    }

    public function getFirstEntry(): ?array
    {
        return $this->first_entry > -1 ? $this->entries[$this->first_entry] : null;
    }

    public function getLastEntry(): ?array
    {
        return $this->last_entry > -1 ? $this->entries[$this->last_entry] : null;
    }

    public function sanitize(): void
    {
        $tick = $this->tick();
        foreach ($this->entries as $i => $entry) {
            $this->entries[$i] = Sanitizer::entry($entry);
        }
        $this->timer($tick, __FUNCTION__);
    }

    public function validate(): void
    {
        $tick = $this->tick();
        foreach ($this->entries as $i => $entry) {
            $errors = Validator::entry($entry);
            if (is_array($errors)) {
                if ($this->mode == self::MODE_POTA) {
                    [$clean, $errors] = Sanitizer::filter($entry, $errors, Spec::$pota_optional);
                    $this->entries[$i] = $clean;
                }
                if (is_array($errors)) {
                    $this->errors[$i] = $errors;
                }
            }
        }
        if ($this->check_qps) {
            if (! Validator::duration($this->entries)) {
                $this->errors['@'][] = 'qps';
            }
        }
        $this->timer($tick, __FUNCTION__);
    }

    public function hasErrors(): bool
    {
        return count($this->errors) > 0;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function dedupe(): void
    {
        $tick = $this->tick();
        $hashes = [];  // Use array keys as hash set for O(1) lookup
        foreach ($this->entries as $i => $entry) {
            $hash = [];
            foreach (Spec::$pota_unique as $k) {
                $hash[] = array_key_exists($k, $entry) ? $entry[$k] : '-';
            }
            $hash = implode('|', $hash);
            if (isset($hashes[$hash])) {  // O(1) hash lookup instead of O(n) in_array
                $this->duplicates[$i] = $entry;
            } else {
                $hashes[$hash] = true;  // Store as key, not value
            }
        }
        $ds = array_keys($this->duplicates);
        foreach ($ds as $d) {
            unset($this->entries[$d]);
        }
        $this->count = count($this->entries);
        $this->timer($tick, __FUNCTION__);
    }

    public function hasDupes(): bool
    {
        return count($this->duplicates) > 0;
    }

    public function getDupes(): array
    {
        return $this->duplicates;
    }

    public function morph(int $mode = Adif::MORPH_ADIF_STRICT): void
    {
        $tick = $this->tick();
        $filter = null;
        switch ($mode) {
            case Adif::MORPH_ADIF_STRICT:
                $filter = Spec::$enum_field;
                break;
            case Adif::MORPH_POTA_ONLY:
                $filter = Spec::$pota_fields;
                break;
            case Adif::MORPH_POTA_REFS:
                $this->unroll_pota_refs();
                break;
        }
        if (! empty($filter)) {
            // Convert array to hash set for O(1) lookups
            $filter_set = array_flip($filter);
            foreach ($this->entries as $i => $entry) {
                $this->entries[$i] = array_filter($entry, fn ($key) => isset($filter_set[$key]), ARRAY_FILTER_USE_KEY);
            }
        }
        $this->timer($tick, __FUNCTION__);
    }

    public function unroll_pota_refs(): void
    {
        $tick = $this->tick();
        foreach ($this->entries as $i => $entry) {

            $isMySigInfo = isset($entry['my_sig_info']);
            $isSigInfo = isset($entry['sig_info']);
            $isMyPotaRef = isset($entry['my_pota_ref']);
            $isPotaRef = isset($entry['pota_ref']);

            // mangle my_sig_info into my_pota_ref
            if ($isMySigInfo && ! $isMyPotaRef) {
                $entry['my_pota_ref'] = $entry['my_sig_info'];
            }
            // mangle sig_info into pota_ref
            if ($isSigInfo && ! $isPotaRef) {
                $entry['pota_ref'] = $entry['sig_info'];
            }

            $isActivatorFer = isset($entry['my_pota_ref']) && strpos($entry['my_pota_ref'], ',');
            $isHunterFer = isset($entry['pota_ref']) && strpos($entry['pota_ref'], ',');
            $isActivatorLoc = isset($entry['my_pota_ref']) && strpos($entry['my_pota_ref'], '@');
            $isHunterLoc = isset($entry['pota_ref']) && strpos($entry['pota_ref'], '@');

            // both sides are in -fers
            if ($isActivatorFer && $isHunterFer) {
                $this->removeEntry($i);
                $new_entry = $entry;
                $new_entry['pota_unrolled_from_rec'] = $i;

                foreach (explode(',', $entry['my_pota_ref']) as $mpr) {
                    $mprparts = explode('@', $mpr);
                    $myref = $mprparts[0];
                    $mystate = $mprparts[1] ?? null;
                    if (isset($mystate)) {
                        $new_entry['pota_my_location'] = trim($mystate);
                    }
                    $new_entry['pota_my_park_ref'] = trim($myref);

                    foreach (explode(',', $entry['pota_ref']) as $pr) {
                        $prparts = explode('@', $pr);
                        $theirref = $prparts[0];
                        $theirstate = $prparts[1] ?? null;
                        if (isset($theirstate)) {
                            $new_entry['pota_location'] = trim($theirstate);
                        }
                        $new_entry['pota_park_ref'] = trim($theirref);
                        $this->addEntry($new_entry);
                    }
                }
            }

            // only activator side in -fer
            elseif ($isActivatorFer && ! $isHunterFer) {
                $this->removeEntry($i);
                $new_entry = $entry;
                $new_entry['pota_unrolled_from_rec'] = $i;
                foreach (explode(',', $entry['my_pota_ref']) as $mpr) {
                    $mprparts = explode('@', $mpr);
                    $myref = $mprparts[0];
                    $mystate = $mprparts[1] ?? null;
                    if (isset($mystate)) {
                        $new_entry['pota_my_location'] = trim($mystate);
                    }
                    $new_entry['pota_my_park_ref'] = trim($myref);

                    // check for state specified in hunted side
                    if (isset($entry['pota_ref'])) {
                        $prparts = explode('@', $entry['pota_ref']);
                        $theirref = $prparts[0];
                        $theirstate = $prparts[1] ?? null;
                        if (isset($theirstate)) {
                            $new_entry['pota_location'] = trim($theirstate);
                        }
                        $new_entry['pota_park_ref'] = trim($theirref);

                    }
                    $this->addEntry($new_entry);
                }
            }

            // only hunter side in -fer
            elseif ($isHunterFer && ! $isActivatorFer) {
                $this->removeEntry($i);
                $new_entry = $entry;
                $new_entry['pota_unrolled_from_rec'] = $i;

                foreach (explode(',', $entry['pota_ref']) as $pr) {
                    $prparts = explode('@', $pr);
                    $theirref = $prparts[0];
                    $theirstate = $prparts[1] ?? null;
                    if (isset($theirstate)) {
                        $new_entry['pota_location'] = trim($theirstate);
                    }
                    $new_entry['pota_park_ref'] = trim($theirref);

                    // check for state specified in activator side
                    if (isset($entry['my_pota_ref'])) {
                        $mprparts = explode('@', $entry['my_pota_ref']);
                        $myref = $mprparts[0];
                        $mystate = $mprparts[1] ?? null;
                        if (isset($mystate)) {
                            $new_entry['pota_my_location'] = trim($mystate);
                        }
                        $new_entry['pota_my_park_ref'] = trim($myref);
                    }
                    $this->addEntry($new_entry);
                }
            }

            // neither side in -fers, check for @locs
            elseif (! $isActivatorFer && ! $isHunterFer) {
                if ($isActivatorLoc) {
                    $mprparts = explode('@', $entry['my_pota_ref']);
                    $myref = $mprparts[0];
                    $mystate = $mprparts[1] ?? null;
                    if (isset($mystate)) {
                        $this->entries[$i]['pota_my_location'] = trim($mystate);
                    }
                    $this->entries[$i]['pota_unrolled_from_rec'] = $i;
                    $this->entries[$i]['pota_my_park_ref'] = trim($myref);
                } else {
                    // no -fer, no @log
                    if (isset($entry['my_pota_ref'])) {
                        $this->entries[$i]['pota_my_park_ref'] = $entry['my_pota_ref'];
                    }
                }
                if ($isHunterLoc) {
                    $mprparts = explode('@', $entry['pota_ref']);
                    $theirref = $mprparts[0];
                    $theirstate = $mprparts[1] ?? null;
                    if (isset($theirstate)) {
                        $this->entries[$i]['pota_location'] = trim($theirstate);
                    }
                    $this->entries[$i]['pota_unrolled_from_rec'] = $i;
                    $this->entries[$i]['pota_park_ref'] = trim($theirref);
                } else {
                    // no -fer, no @log
                    if (isset($entry['pota_ref'])) {
                        $this->entries[$i]['pota_park_ref'] = $entry['pota_ref'];
                    }
                }
            }
        }
        $this->timer($tick, __FUNCTION__);
    }

    public function chunk(int $size = Adif::CHUNK_MAX_SIZE): void
    {
        $tick = $this->tick();
        $current_size = strlen(json_encode($this->entries));
        if ($current_size > $size) {
            $chunk_size = 0;
            $chunk_data = [];
            foreach ($this->entries as $i => $entry) {
                $entry_size = strlen(json_encode($entry));
                if ($chunk_size + $entry_size <= $size) {
                    $chunk_data[] = $i;
                    $chunk_size += $entry_size;
                } else {
                    $this->chunks[] = $chunk_data;
                    $chunk_data = [];
                    $chunk_size = $entry_size;
                    $chunk_data[] = $i;
                }
            }
            if (count($chunk_data)) {
                $this->chunks[] = $chunk_data;
            }
        } else {
            $this->chunks[] = array_keys($this->entries);
        }
        $this->timer($tick, __FUNCTION__);
    }

    private function generateAdifKeyValue(string $key, string $value, bool $newline = true): string
    {
        $key = trim(strtolower($key));
        $value = trim($value);

        return '<' . $key . ':' . strlen($value) . '>' . $value . ($newline ? PHP_EOL : '');
    }

    public function generateAdifHeaders(): string
    {
        $hs = [];
        foreach ($this->sources as $s) {
            $hs[] = 'Source [' . implode(', ', $s) . ']' . PHP_EOL;
        }
        $hs[] = $this->generateAdifKeyValue('adif_version', '3.1.6');
        $hs[] = $this->generateAdifKeyValue('created_timestamp', date('Ymd His'));
        $hs[] = $this->generateAdifKeyValue('programid', 'POTA-ADIF');
        $hs[] = $this->generateAdifKeyValue('programversion', '2.0.0');

        return trim(implode($hs)) . PHP_EOL . '<eoh>' . PHP_EOL . PHP_EOL;
    }

    public function generateAdifEntries(): string
    {
        $recs = [];
        foreach ($this->entries as $entry) {
            $rec = '';
            foreach ($entry as $k => $v) {
                $rec .= $this->generateAdifKeyValue($k, $v);
            }
            $recs[] = trim($rec) . PHP_EOL . '<eor>';
        }

        return implode(PHP_EOL . PHP_EOL, $recs);
    }

    public function toAdif(): string
    {
        $tick = $this->tick();
        $data = $this->generateAdifHeaders() . $this->generateAdifEntries();
        $this->timer($tick, __FUNCTION__);

        return $data;
    }

    public function toJson(bool $pretty = false): string
    {
        $tick = $this->tick();
        $data = ['timers' => [], 'meta' => [], 'headers' => [], 'entries' => []];
        if (count($this->sources)) {
            $data['meta']['sources'] = $this->sources;
        }
        $data['meta']['count'] = count($this->entries);
        $data['meta']['duplicates'] = count($this->duplicates);
        $data['meta']['errors'] = count($this->errors);
        if (count($this->headers)) {
            $data['headers'] = $this->headers;
        }
        if (count($this->duplicates)) {
            $data['duplicates'] = $this->duplicates;
        }
        if (count($this->errors)) {
            $data['errors'] = $this->errors;
        }
        if (count($this->chunks)) {
            $data['meta']['chunks'] = count($this->chunks);
            foreach ($this->chunks as $chunk) {
                $entries = [];
                foreach ($chunk as $i) {
                    $entries[] = $this->entries[$i];
                }
                $data['entries'][] = $entries;
            }
        } elseif (count($this->entries)) {
            $data['entries'] = $this->entries;
        }
        $data['timers'] = $this->timers;
        $tm = 19790212100166;
        $fn = strtolower(__FUNCTION__);
        $data['timers'][$fn] = $tm;
        $json = ($pretty ? json_encode($data, JSON_PRETTY_PRINT) : json_encode($data)) . PHP_EOL;
        $this->timer($tick, $fn);

        return str_replace((string) $tm, (string) $this->getTimers($fn), $json);
    }
}
