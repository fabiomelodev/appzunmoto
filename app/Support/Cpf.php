<?php

namespace App\Support;

/** Validates a Brazilian CPF using the official check-digit algorithm. */
class Cpf
{
    public static function isValid(?string $cpf): bool
    {
        $digits = preg_replace('/\D/', '', (string) $cpf);

        if (strlen($digits) !== 11 || preg_match('/^(\d)\1{10}$/', $digits)) {
            return false;
        }

        for ($position = 9; $position <= 10; $position++) {
            $sum = 0;
            for ($i = 0; $i < $position; $i++) {
                $sum += (int) $digits[$i] * (($position + 1) - $i);
            }
            $checkDigit = ((10 * $sum) % 11) % 10;
            if ((int) $digits[$position] !== $checkDigit) {
                return false;
            }
        }

        return true;
    }
}
