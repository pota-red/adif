<?php

namespace Pota\Adif;

use Pota\Adif\Spec;
use Pota\Adif\Document;
use Pota\Adif\Linter;
use Pota\Adif\Sanitizer;
use Pota\Adif\Validator;

class Adif {

    // Firestore MongoDB max doc size
    public const int CHUNK_MAX_SIZE = 2000000;
    public const int MORPH_ADIF_STRICT = 1;
    public const int MORPH_POTA_ONLY = 2;
    public const int MORPH_POTA_REFS = 3;

    protected array $docs = [];

    public function loadFile(string $path) : int {
        $i = count($this->docs);
        $this->docs[] = new Document($path);
        return $i;
    }

    public function loadString(string $text) : int {
        $i = count($this->docs);
        $this->docs[] = new Document($text);
        return $i;
    }

    public function parse() : void {
        foreach ($this->docs as $doc) {
            $doc->parse();
        }
    }

    public function merge() : Document {
        if (count($this->docs) > 1) {
            $new = new Document;
            foreach ($this->docs as $doc) {
                $new->addTimer('parse', $doc->getTimers('parse'));
                $hs = ["fn={$doc->filename}", "ec={$doc->count}"];
                $h = $doc->getHeaders('programid');
                if (!empty($h)) {
                    $hs[] = "pn=$h";
                }
                $h = $doc->getHeaders('programversion');
                if (!empty($h)) {
                    $hs[] = "pv=$h";
                }
                $new->addSource($hs);
                $new->addEntry($doc->getEntries());
            }
            return $new;
        } else {
            return $this->docs[0];
        }
    }

}


