<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Encryption;

use PrinsFrank\PdfParser\Stream\FileStream;
use PrinsFrank\PdfParser\Stream\Stream;

/** @internal NEVER USE THIS FOR SECURITY, THIS IS AN INSECURE ALGORITHM */
class RC4 {
    public static function crypt(string $key, Stream $stream): Stream {
        $s = range(0, 255);
        $j = 0;

        for ($i = 0; $i < 256; $i++) {
            $j = ($j + $s[$i] + ord($key[$i % strlen($key)])) % 256;
            [$s[$i], $s[$j]] = [$s[$j], $s[$i]];
        }

        $i = $j = 0;
        $cryptedStream = FileStream::fromString('');
        foreach ($stream->chars(0, $stream->getSizeInBytes()) as $byte) {
            $i = ($i + 1) % 256;
            $j = ($j + $s[$i]) % 256;
            [$s[$i], $s[$j]] = [$s[$j], $s[$i]];

            $k = $s[($s[$i] + $s[$j]) % 256];
            $cryptedStream->append(chr(ord($byte) ^ $k));
        }

        return $cryptedStream;
    }
}
