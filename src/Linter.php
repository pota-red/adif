<?php

namespace Pota\Adif;

class Linter {

    public static $BAD_FORM = ['@' => 'ADIF requires <eoh> and at least one <eor>'];

    public static function lintPota(string $text) : array {
        $parts = array_filter(preg_split('/<eor>/i', $text));
        if (count($parts) === 0) {
            return self::$BAD_FORM;
        }

        $fields = Spec::$base_fields;
        $errors = [];
        foreach ($parts as $i => $part) {
            foreach ($fields as $field) {
                if (!preg_match('/<' . $field . ':[0-9]*>/is', $part)) {
                    $errors[$i][] = $field;
                }
            }
        }
        return $errors;
    }

    public static function lint(string $text, int $mode) : array {
        $text = trim($text);
        $basic = preg_match('/<eoh>.*<eor>/is', $text);
        if ($basic && $mode == Document::MODE_POTA) {
            return self::lintPota($text);
        }
        return $basic ? [] : self::$BAD_FORM;
    }

}
