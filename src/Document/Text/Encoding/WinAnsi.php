<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Text\Encoding;

use Override;
use PrinsFrank\PdfParser\Exception\ParseFailureException;

class WinAnsi implements Encoding {
    /** @throws ParseFailureException */
    #[Override]
    public static function textToUnicode(string $string): string {
        $string = iconv(
            'Windows-1252',
            'UTF-8//IGNORE',
            $string
        );

        if ($string === false) {
            throw new ParseFailureException();
        }

        return $string;
    }
}
