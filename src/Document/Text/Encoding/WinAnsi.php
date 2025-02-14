<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Text\Encoding;

use Override;
use PrinsFrank\PdfParser\Exception\ParseFailureException;

class WinAnsi implements Encoding {
    /** @throws ParseFailureException */
    #[Override]
    public static function textToUnicode(string $string): string {
        if (mb_detect_encoding($string, strict: true) === 'UTF-8') {
            return $string;
        }

        $string = mb_convert_encoding(
            $string,
            'UTF-8',
            'Windows-1252',
        );

        if ($string === false) {
            throw new ParseFailureException();
        }

        return $string;
    }
}
