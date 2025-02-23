# ADIF Library

A general purpose ADIF handling library.

## Features

* Load ADIF from file or string
* Parse ADIF
* Merge multiple files
* Sanitize data - cleans up common typos, etc
* Validate data - Generic rules based on ADIF 3.1.5
* Deduplicate entries - Based on POTA unique contact rules
* Morph data - Strip entry data to ADIF core spec or POTA required fields
* Generate ADIF
* Generate JSON

## Components

* Adif - loader / merger
* Document - primary ADIF container
* Spec - Enums, Validation functions
* Sanitizer - Sanitization logic
* Validator - Validation logic

## Usage

### Fresh ADIF
```
$doc = new Document;
$doc->addEntry(array $adif_fields);
```

### Single File
```
$doc = new Document(string $path_to_file);
$doc->parse();
$headers = $doc->getHeaders();
$entries = $doc->getEntries();
```

### Multiple Files
```
$lib = new Adif;
$lib->loadFile(string $path_to_file_1);
...
$lib->loadFile(string $path_to_file_999);
$lib->parse();
$doc = $lib->merge();
unset($lib);
```

---

After running `parse()`:

### Sanitize

```$doc->sanitize();```

### Validate
```
$doc->validate();
if ($doc->hasErrors()) {
    $errors = $doc->getErrors();
}
```
Array keys in `getErrors()` are the sequential array key from the `getEntries()`. This makes it possible to collate errors to data.


### Deduplicate
```
$doc->dedupe();
if ($doc->hasDupes()) {
    $dupes = $doc->getDupes();
}
```
Array keys in `getDupes()` are the sequential array key from the `getEntries()`. This makes it possible to collate duplicates to data.

### Morph

Morph modes:
1. `MORPH_ADIF_STRICT` -- remove entry fields which are not explicitly names in the specification
2. `MORPH_POTA_ONLY` -- remove fields which are not used by POTA

```
$doc->morph(Adif::MORPH_ADIF_STRICT);
``` 

--- 

### Output

#### ADIF
`$adif = $doc->toAdif();`

#### JSON
`$json = $doc->toJson();`

---

### Timers

Processing methods track execution time.

Get all the timers as `array` with

`$timers = $doc->getTimers();`

Get specific process timer as `float` with

`$timer = $doc->getTimers('parse');`

## Spec

There are many useful enumerations and basic validation log from the ADIF spec available in the `Spec` component.

## Example

### Processing

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

$adif = $doc->toAdif();
file_put_contents('log-merged.adif', $adif);
unset($adif);

$json = $doc->toJson();
file_put_contents('log-merged.json', $json);
unset($json);

print_r($doc->getTimers());
unset($doc);
```

### Results

This testing was on an average workstation:
* Intel i7 6700 @ 2.8Ghz
* 16GB RAM
* SSD storage
* Fedora 41
* PHP 8.3

The processing was limited to a single CPU core. PHP memory_limit was 512MB.

Total processing time was 161,467 ms - about 2.7 minutes.

#### Input
* 48 files
* 42.5 MB

#### Parse
* 142,105 log entries
* 1515 ms execution time

#### Sanitize
* 802 ms execution time

#### Validate
* 590 ms execution time

#### Deduplicate
* 28,874 duplicate log entries
* 157,567 ms execution time

#### Morph
* 542 ms execution time

#### Output
* 113,231 log entries

##### ADIF
* 16.2 MB
* 349 ms execution time

##### JSON
* 17.4 MB
* 102 ms execution time