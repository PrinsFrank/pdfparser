<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Dictionary\Normalization;

use PrinsFrank\PdfParser\Exception\RuntimeException;

class NameValueNormalizer {
    /** @throws RuntimeException */
    public static function normalize(string $name): string {
        $value = ltrim($name, '/');
        $value = preg_replace_callback(
            '/#([0-9A-Fa-f]{2})/',
            static function (array $matches): string {
                if (!is_int($codePoint = hexdec($matches[1]))) {
                    throw new RuntimeException('An unexpected error occurred while normalizing name value');
                }

                return mb_chr($codePoint, 'UTF-8');
            },
            $value,
        ) ?? throw new RuntimeException('An unexpected error occurred while normalizing name value');

        return trim($value);
    }
}
