<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Tests\Unit\Document\Version;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use PrinsFrank\PdfParser\Document\Version\Version;

#[CoversClass(Version::class)]
class VersionTest extends TestCase {
    public function testLength(): void {
        static::assertSame(3, Version::length());
        foreach (Version::cases() as $case) {
            static::assertSame(Version::length(), strlen($case->value));
        }
    }
}
