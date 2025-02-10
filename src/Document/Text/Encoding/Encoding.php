<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Text\Encoding;

/** @api */
interface Encoding {
    public static function textToUnicode(string $string): string;
}
