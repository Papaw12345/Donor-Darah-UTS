<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class MySqlTextBytes implements ValidationRule
{
    public const MAX_BYTES = 65535;

    public static function exceedsCapacity(string $value): bool
    {
        return strlen($value) > self::MAX_BYTES;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (is_string($value) && self::exceedsCapacity($value)) {
            $fail(':attribute melebihi kapasitas penyimpanan teks.');
        }
    }
}