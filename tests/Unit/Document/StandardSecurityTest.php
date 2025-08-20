<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Tests\Unit\Document;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use PrinsFrank\PdfParser\Document\Security\StandardSecurity;

#[CoversClass(StandardSecurity::class)]
class StandardSecurityTest extends TestCase {
    public function testGetPaddedUserPassword(): void {
        static::assertSame(
            "\x28\xBF\x4E\x5E\x4E\x75\x8A\x41\x64\x00\x4E\x56\xFF\xFA\x01\x08\x2E\x2E\x00\xB6\xD0\x68\x3E\x80\x2F\x0C\xA9\xFE\x64\x53\x69\x7A",
            (new StandardSecurity(null, null))->getPaddedUserPassword()
        );
        static::assertSame(
            "\x28\xBF\x4E\x5E\x4E\x75\x8A\x41\x64\x00\x4E\x56\xFF\xFA\x01\x08\x2E\x2E\x00\xB6\xD0\x68\x3E\x80\x2F\x0C\xA9\xFE\x64\x53\x69\x7A",
            (new StandardSecurity('', null))->getPaddedUserPassword()
        );
        static::assertSame(
            "a\x28\xBF\x4E\x5E\x4E\x75\x8A\x41\x64\x00\x4E\x56\xFF\xFA\x01\x08\x2E\x2E\x00\xB6\xD0\x68\x3E\x80\x2F\x0C\xA9\xFE\x64\x53\x69",
            (new StandardSecurity('a', null))->getPaddedUserPassword()
        );
        static::assertSame(
            "abcdefghijklmnopqrstuvwxyz0123\x28\xBF",
            (new StandardSecurity('abcdefghijklmnopqrstuvwxyz0123', null))->getPaddedUserPassword()
        );
        static::assertSame(
            "abcdefghijklmnopqrstuvwxyz012345",
            (new StandardSecurity('abcdefghijklmnopqrstuvwxyz012345', null))->getPaddedUserPassword()
        );
        static::assertSame(
            "abcdefghijklmnopqrstuvwxyz012345",
            (new StandardSecurity('abcdefghijklmnopqrstuvwxyz0123456789', null))->getPaddedUserPassword()
        );
    }
}
