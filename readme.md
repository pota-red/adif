# ADIF Library

A general purpose ADIF handling library.

## Features

* Load ADIF from file or string
* Parse ADIF
* Merge multiple files
* Sanitize data - cleans up common typoes (eg: hypens in dates, colons in times, etc)
* Validate data - Generic rules based on ADIF 3.1.5
* Deduplicate entries - Based on POTA unique contact rules
* Morph data - Strip entry data to ADIF core spec or POTA supported fields
* Chunk data - Split entries based on size (useful for storage targets with document size limits - eg: Google Firestore)
* Generate ADIF
* Generate JSON

## Components

* Adif - loader / merger
* Document - primary ADIF container
* Spec - Enums, Validation functions
* Sanitizer - Sanitization logic
* Validator - Validation logic

## Usage

### Include With `composer`

Add repository and require to your `composer.json`.
```
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/pota-red/adif"
        }
    ],
    "require": {
        "pota/adif": "@dev"
    }
}
```
Run `composer update`.


### Setup Your Script

```
require 'vendor/autoload.php';

use Pota\Adif\Adif;
use Pota\Adif\Document;
```

Full usage example at [example.md](example.md).

### Fresh ADIF
```
$doc = new Document;
$doc->addEntry(array $adif_fields);
```
### From String
```
$doc = new Document(string $adif_data);
$doc->parse();
$headers = $doc->getHeaders();
$entries = $doc->getEntries();
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
1. `MORPH_ADIF_STRICT` -- remove entry fields which are not explicitly named in the specification
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

