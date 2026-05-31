<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Filter\Decode;

use PrinsFrank\PdfParser\Exception\RuntimeException;

class ASCIIHexDecode {
    /** @throws RuntimeException */
    public static function decodeBinary(string $content): string {
        $decodedContent = '';
        $strlen = strlen($content);
        for ($i = 0; $i < $strlen; $i += 2) {
            $charCode = hexdec(substr($content, $i, 2));
            if (is_float($charCode)) {
                throw new RuntimeException('Invalid hex string');
            }

            $decodedContent .= chr($charCode);
        }

        if ($strlen % 2 !== 0) {
            $charCode = hexdec(substr($content, $strlen - 1, 1));
            if (is_float($charCode)) {
                throw new RuntimeException('Invalid hex string');
            }

            $decodedContent .= chr($charCode);
        }

        return $decodedContent;
    }
}
