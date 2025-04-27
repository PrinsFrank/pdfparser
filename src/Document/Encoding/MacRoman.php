<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Encoding;

use Override;
use PrinsFrank\PdfParser\Exception\ParseFailureException;

class MacRoman implements Encoding {
    /** @throws ParseFailureException */
    #[Override]
    public static function textToUnicode(string $string): string {
        if (mb_detect_encoding($string, strict: true) === 'UTF-8') {
            return $string;
        }

        $string = iconv(
            'macintosh',
            'UTF-8//TRANSLIT',
            $string
        );

        if ($string === false) {
            throw new ParseFailureException();
        }

        return $string;
    }
}
