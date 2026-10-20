<?php

namespace App\Support;

/**
 * Neutralises CSV/XLSX formula injection: a registrant-supplied cell that starts with
 * = + - @ (or tab / CR) is prefixed with an apostrophe so spreadsheets treat it as text.
 */
class SpreadsheetSanitizer
{
    public static function cell(mixed $value): mixed
    {
        // Plain numbers / phone numbers ("+254 712-345", "-5") cannot invoke functions; leave them intact.
        if (is_string($value) && preg_match('/^[+-]?[\d\s().-]+$/', $value)) {
            return $value;
        }

        if (is_string($value) && $value !== '' && in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
            return "'".$value;
        }

        return $value;
    }
}
