<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Tests\Unit\Document\Dictionary;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use PrinsFrank\PdfParser\Document\Dictionary\Dictionary;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryEntry\DictionaryEntry;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryKey\DictionaryKey;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Name\SubtypeNameValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Name\TypeNameValue;

#[CoversClass(Dictionary::class)]
class DictionaryTest extends TestCase {
    public function testGetType(): void {
        static::assertSame(
            TypeNameValue::FILE_SPEC,
            (new Dictionary(new DictionaryEntry(DictionaryKey::TYPE, TypeNameValue::FILE_SPEC)))
                ->getType(null),
        );
    }

    public function testGetTypeReturnsTypeFromSubDictionary(): void {
        static::assertSame(
            TypeNameValue::FILE_SPEC,
            (new Dictionary(new DictionaryEntry(DictionaryKey::TYPE, new Dictionary(new DictionaryEntry(DictionaryKey::TYPE, TypeNameValue::FILE_SPEC)))))
                ->getType(null),
        );
    }

    public function testGetSubType(): void {
        static::assertSame(
            SubTypeNameValue::FORM,
            (new Dictionary(new DictionaryEntry(DictionaryKey::SUBTYPE, SubtypeNameValue::FORM)))
                ->getSubType(null),
        );
    }

    public function testGetSubTypeReturnsSubTypeFromSubDictionary(): void {
        static::assertSame(
            SubTypeNameValue::FORM,
            (new Dictionary(new DictionaryEntry(DictionaryKey::SUBTYPE, new Dictionary(new DictionaryEntry(DictionaryKey::SUBTYPE, SubtypeNameValue::FORM)))))
                ->getSubType(null),
        );
    }
}
