<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Tests\Unit\Document\Dictionary\DictionaryKey;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryKey\DictionaryKey;
use PrinsFrank\PdfParser\Exception\RuntimeException;

#[CoversClass(DictionaryKey::class)]
class DictionaryKeyTest extends TestCase {
    #[DataProvider('cases')]
    public function testNameFormat(DictionaryKey $dictionaryKey): void {
        $expectedName = strtoupper(preg_replace('/([a-z])([A-Z])/', '$1_$2', str_replace('.', '_', $dictionaryKey->value)) ?? throw new RuntimeException());
        if ($expectedName !== $dictionaryKey->name && defined(DictionaryKey::class . '::' . $expectedName) && ctype_lower(substr($dictionaryKey->value, -1))) {
            $expectedName .= '_L';
        }

        if (str_starts_with($expectedName, '3')) { // enum cases cannot start with a digit
            $expectedName = 'THREE_' . substr($expectedName, 1);
        }

        static::assertSame(
            $expectedName,
            $dictionaryKey->name,
            sprintf('Name of DictionaryKey case %s should be UPPER_CASE version %s of value', $dictionaryKey->name, $expectedName),
        );
    }

    public function testTryFromKeyString(): void {
        self::assertNull(DictionaryKey::tryFromKeyString(''));
        self::assertNull(DictionaryKey::tryFromKeyString('Foo'));
        self::assertSame(
            DictionaryKey::ZOOM,
            DictionaryKey::tryFromKeyString('Zoom'),
        );
        self::assertSame(
            DictionaryKey::ZOOM,
            DictionaryKey::tryFromKeyString('/Zoom'),
        );
        self::assertSame(
            DictionaryKey::ZOOM,
            DictionaryKey::tryFromKeyString('/Zoom  '),
        );
        self::assertSame(
            DictionaryKey::ZOOM,
            DictionaryKey::tryFromKeyString('/Zoom
            '),
        );
    }

    /** @return iterable<array<DictionaryKey>> */
    public static function cases(): iterable {
        foreach (DictionaryKey::cases() as $dictionaryKey) {
            yield [$dictionaryKey];
        }
    }
}
