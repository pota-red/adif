# ADIF Library

## Example Usage

Testing was done using multiple large logs from a DXpedition.

```
require_once 'vendor/autoload.php';

use RFingAround\Adif\Adif;

$lib = new Adif;

$files = glob('logs/*.adif');
foreach ($files as $file) {
    $lib->loadFile($file);
}
$lib->parse();
$doc = $lib->merge();
unset($lib);

$doc->sanitize();
$doc->validate();
$doc->dedupe();
$doc->morph(Adif::MORPH_POTA_ONLY);

file_put_contents('log-merged.adif', $doc->toAdif());

file_put_contents('log-merged.json', $doc->toJson());

$doc->chunk();
file_put_contents('log-merged-chunked.json', $doc->toJson());

print_r($doc->getTimers());
unset($doc);
```

---

## Results

This testing was on an average workstation:
* Intel i7 6700 @ 2.8Ghz
* 16GB RAM
* SSD storage
* Fedora 41
* PHP 8.3

The processing was limited to a single CPU core. PHP memory_limit was 512MB.

Total processing time was 161,467 ms - about 2.7 minutes.

### Input
* 48 files
* 42.5 MB

### Parse
* 142,105 log entries
* 1515 ms execution time

### Sanitize
* 802 ms execution time

### Validate
* 590 ms execution time

### Deduplicate
* 28,874 duplicate log entries
* 157,567 ms execution time

### Morph
* 542 ms execution time

### Output
* 113,231 log entries

#### ADIF
* 16.2 MB
* 349 ms execution time

#### JSON
* 17.4 MB
* 102 ms execution time
* 