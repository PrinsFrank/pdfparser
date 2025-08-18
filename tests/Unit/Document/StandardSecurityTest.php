<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Tests\Unit\Document;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use PrinsFrank\PdfParser\Document\Security\StandardSecurity;
use PrinsFrank\PdfParser\Document\Security\StandardSecurityHandlerRevision;

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

    public function testGetFileEncryptionKey(): void {
        static::assertSame(
            '942c5e7b20',
            bin2hex((new StandardSecurity('123456', null))->getFileEncryptionKey("\xC4\x31\xFA\xB9\xCC\x5E\xF7\xB5\x9C\x24\x4B\x61\xB7\x45\xF7\x1A\xC5\xBA\x42\x7B\x1B\x91\x02\xDA\x46\x8E\x77\x12\x7F\x1E\x69\xD6", -4, "\xB5\x18\x5D\x94\x1C\xC0\xEA\x39\xAC\xA8\x09\xF6\x61\xEF\x36\xD4\x39\x3B\xE7\x25\x53\x2F\x91\x58\xDC\x9E\x6E\x8E\xA9\x7C\xFB\xF0", StandardSecurityHandlerRevision::v2, null)), // Example from https://stackoverflow.com/questions/71535246/generating-pdf-user-password-hash with invalid ID
        );
        static::assertSame(
            'f821675672',
            bin2hex((new StandardSecurity('123456', null))->getFileEncryptionKey("\xC4\x31\xFA\xB9\xCC\x5E\xF7\xB5\x9C\x24\x4B\x61\xB7\x45\xF7\x1A\xC5\xBA\x42\x7B\x1B\x91\x02\xDA\x46\x8E\x77\x12\x7F\x1E\x69\xD6", -4, "\xB5\x18\x5D\x94\x1C\xC0\xEA\x39\xAC\xA8\x09\xF6\x61\xEF\x36\xD4", StandardSecurityHandlerRevision::v2, null)), // Example from https://stackoverflow.com/questions/71535246/generating-pdf-user-password-hash with corrected ID
        );
    }
}
