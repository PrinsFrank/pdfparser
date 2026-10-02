<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Tests\Unit\Document\Dictionary\DictionaryEntry;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryEntry\DictionaryEntry;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryKey\ExtendedDictionaryKey;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\TextString\LiteralStringValue;

#[CoversClass(DictionaryEntry::class)]
class DictionaryEntryTest extends TestCase {
    public function testConstruct(): void {
        $dictionaryEntry = new DictionaryEntry(new ExtendedDictionaryKey('Foo'), new LiteralStringValue('Bar'));

        static::assertEquals(new ExtendedDictionaryKey('Foo'), $dictionaryEntry->key);
        static::assertEquals(new LiteralStringValue('Bar'), $dictionaryEntry->value);
    }
}
