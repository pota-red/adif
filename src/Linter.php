<?php

namespace Pota\Adif;

class Linter {

    public static function lint(string $text, int $mode) : array {
        $bad_form = ['@' => 'ADIF requires <eoh> and at least one <eor>'];
        $text = trim($text);
        $basic = preg_match('/<eoh>.*<eor>/is', $text);
        if ($basic && $mode == Document::MODE_POTA) {
            $parts = preg_split('/<eor>/i', $text);
            if (count($parts) > 0) {
                $fields = Spec::$base_fields;
                $errors = [];
                foreach ($parts as $i => $part) {
                    if (!empty($part)) {
                        foreach ($fields as $field) {
                            if (!preg_match('/<' . $field . ':[0-9]*>/is', $part)) {
                                $errors[$i][] = $field;
                            }
                        }
                    }
                }
                return $errors;
            } else {
                return $bad_form;
            }
        }
        return $basic ? [] : $bad_form;
    }

}
