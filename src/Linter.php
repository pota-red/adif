<?php

namespace Pota\Adif;

class Linter {

    public static function lint(string $text, int $mode) : array {
        $basic = preg_match('/<eoh>.*<eor>/is', $text);
        if ($basic && $mode == Document::MODE_POTA) {
            $parts = preg_split('/<eor>/i', $text);
            if (count($parts) > 0) {
                $fields = Spec::$base_fields;
                $errors = [];
                foreach ($parts as $i => $part) {
                    echo '---------------------- PART ------------------', PHP_EOL, $part, PHP_EOL;
                    echo '-----------------------------------------------', PHP_EOL, PHP_EOL;
                    foreach ($fields as $field) {
                        echo '----------------- ', $field, ' ----------------', PHP_EOL;
                        var_dump(preg_match('/<' . $field . '>/is', $part));
                        echo '-----------------------------------------------', PHP_EOL, PHP_EOL;
                        if (!preg_match('/<' . $field . '>/is', $part)) {
                            $errors[$i][] = $field;
                        }
                    }
                }
                return $errors;
            } else {
                return ['@' => 'ADIF requires <eoh> and at least one <eor>'];
            }
        }
        return $basic ? [] : ['@' => 'ADIF requires <eoh> and at least one <eor>'];
    }

}
