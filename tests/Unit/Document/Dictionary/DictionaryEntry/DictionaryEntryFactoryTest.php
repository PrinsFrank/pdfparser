<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Tests\Unit\Document\Dictionary\DictionaryEntry;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryEntry\DictionaryEntry;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryEntry\DictionaryEntryFactory;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryKey\DictionaryKey;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Name\TabsNameValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\TextString\TextStringValue;
use PrinsFrank\PdfParser\Document\Version\Version;

#[CoversClass(DictionaryEntryFactory::class)]
class DictionaryEntryFactoryTest extends TestCase {
    public function testFromKeyValuePair(): void {
        static::assertEquals(
            new DictionaryEntry(DictionaryKey::TABS, TabsNameValue::StructureOrder),
            DictionaryEntryFactory::fromKeyValuePair('/Tabs', 'S')
        );
        static::assertEquals(
            new DictionaryEntry(DictionaryKey::TABS, new TextStringValue('(S)')),
            DictionaryEntryFactory::fromKeyValuePair('/Tabs', '(S)'),
            'Bug in LibreOffice: https://bugs.documentfoundation.org/show_bug.cgi?id=155228'
        );
        static::assertEquals(
            new DictionaryEntry(DictionaryKey::VERSION, Version::V1_5),
            DictionaryEntryFactory::fromKeyValuePair('/Version', '/1#2E5'),
            'Support for hex values in name objects, see #7.3.5'
        );
    }
}
