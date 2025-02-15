<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\CMap\ToUnicode;

use PrinsFrank\PdfParser\Exception\InvalidArgumentException;
use PrinsFrank\PdfParser\Exception\ParseFailureException;

class CodePoint {
    /** $codepoint cannot be an int as multiple concatenated single bytes can be more than PHP_INT_MAX */
    public static function toString(string $hexString): string {
        if (!ctype_xdigit($hexString)) {
            throw new InvalidArgumentException(sprintf('Expected hex string, got "%s"', $hexString));
        }

        if (strlen($hexString) <= 4 && ($codepoint = (int) hexdec($hexString)) < 0xFFFF) { // BMP
            if (($char = mb_chr($codepoint)) === false) {
                throw new ParseFailureException();
            }

            return $char;
        }

        if (($highSurrogate = ($codepoint = hexdec($hexString) >> 16) & 0xFFFF) >= 0xD800
            && $highSurrogate <= 0xDBFF
            && ($lowSurrogate = $codepoint & 0xFFFF) >= 0xDC00
            && $lowSurrogate <= 0xDFFF) {
            if (($char = mb_chr((($highSurrogate - 0xD800) << 10) + ($lowSurrogate - 0xDC00) + 0x10000)) === false) {
                throw new ParseFailureException();
            }

            return $char;
        }

        $chars = [];
        for ($i = 0; $i < strlen($hexString);) {
            if (($highSurrogate = (($surrogateCodePoint = (int) hexdec(substr($hexString, $i, 8))) >> 16) & 0xFFFF) >= 0xD800
                && $highSurrogate <= 0xDBFF
                && ($lowSurrogate = $surrogateCodePoint & 0xFFFF) >= 0xDC00
                && $lowSurrogate <= 0xDFFF) {
                $charCodepoint = (($highSurrogate - 0xD800) << 10) + ($lowSurrogate - 0xDC00) + 0x10000;
                $i += 8; // Surrogate Pairs are 4 bytes long
            } else {
                $charCodepoint = (int) hexdec(substr($hexString, $i, 4));
                $i += 4; // Non surrogate pairs are 2 bytes long
            }

            if (($char = mb_chr($charCodepoint)) === false) {
                throw new ParseFailureException();
            }

            $chars[] = $char;
        }

        return implode('', $chars);
    }
}
