<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Encryption;

/** @internal NEVER USE THIS FOR SECURITY, THIS IS AN INSECURE ALGORITHM */
class RC4 {
    public static function crypt(string $key, string $data): string {
        $s = range(0, 255);
        $j = 0;

        for ($i = 0; $i < 256; $i++) {
            $j = ($j + $s[$i] + ord($key[$i % strlen($key)])) % 256;
            [$s[$i], $s[$j]] = [$s[$j], $s[$i]];
        }

        $i = $j = 0;
        $output = '';
        foreach (str_split($data) as $byte) {
            $i = ($i + 1) % 256;
            $j = ($j + $s[$i]) % 256;
            [$s[$i], $s[$j]] = [$s[$j], $s[$i]];

            $k = $s[($s[$i] + $s[$j]) % 256];
            $output .= chr(ord($byte) ^ $k);
        }

        return $output;
    }
}
