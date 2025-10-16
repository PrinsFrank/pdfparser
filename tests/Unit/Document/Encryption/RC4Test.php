<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Tests\Unit\Document\Encryption;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use PrinsFrank\PdfParser\Document\Encryption\RC4;
use PrinsFrank\PdfParser\Stream\InMemoryStream;

#[CoversClass(RC4::class)]
class RC4Test extends TestCase {
    public function testCrypt(): void {
        static::assertSame(
            bin2hex("\xBB\xF3\x16\xE8\xD9\x40\xAF\x0A\xD3"),
            bin2hex(RC4::crypt('Key', new InMemoryStream('Plaintext'))->toString()),
        );
        static::assertSame(
            bin2hex("\x10\x21\xBF\x04\x20"),
            bin2hex(RC4::crypt('Wiki', new InMemoryStream('pedia'))->toString()),
        );
        static::assertSame(
            bin2hex("\x45\xA0\x1F\x64\x5F\xC3\x5B\x38\x35\x52\x54\x4B\x9B\xF5"),
            bin2hex(RC4::crypt('Secret', new InMemoryStream('Attack at dawn'))->toString()),
        );
    }
}
