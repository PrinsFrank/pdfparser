<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Filter\Decode;

use PrinsFrank\PdfParser\Exception\RuntimeException;

class ASCII85Decode {
    /** @throws RuntimeException */
    public static function decodeBinary(string $string): string {
        file_put_contents(__DIR__ . '/ascii85.txt', $string);
        $string = trim($string);
        if (str_starts_with($string, '<~') && str_ends_with($string, '~>')) {
            $string = substr($string, 2, -2);
        }

        $string = preg_replace('/\s+/', '', $string)
            ?? throw new RuntimeException('An unexpected error occurred while sanitizing ASCII85 string');
        $length = strlen($string);
        $decoded = $block = '';
        for ($i = 0; $i < $length; ++$i) {
            $char = $string[$i];
            if ($char === 'z') {
                $decoded .= "\0\0\0\0";
                continue;
            }

            $block .= $char;
            if (strlen($block) === 5) {
                $value = 0;
                for ($j = 0; $j < 5; ++$j) {
                    $value = $value * 85 + (ord($block[$j]) - 33);
                }

                $decoded .= pack('N', $value);
                $block = '';
            }
        }

        if ($block !== '') {
            $padding = 5 - strlen($block);
            $block = str_pad($block, 5, 'u');
            $value = 0;
            for ($i = 0; $i < 5; ++$i) {
                $value = $value * 85 + (ord($block[$i]) - 33);
            }

            $binaryData = pack('N', $value);
            $decoded .= substr($binaryData, 0, 4 - $padding);
        }

        return $decoded;
    }
}
