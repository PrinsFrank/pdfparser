<?php
declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\CrossReference\Source;

use PrinsFrank\PdfParser\Document\CrossReference\Source\Section\CrossReferenceSection;
use PrinsFrank\PdfParser\Document\CrossReference\Source\Section\SubSection\Entry\CrossReferenceEntryCompressed;
use PrinsFrank\PdfParser\Document\CrossReference\Source\Section\SubSection\Entry\CrossReferenceEntryInUseObject;
use PrinsFrank\PdfParser\Document\Dictionary\Dictionary;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryKey\DictionaryKey;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Array\ArrayValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\DictionaryValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Name\NameValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Reference\ReferenceValue;
use PrinsFrank\PdfParser\Exception\ParseFailureException;

/** Can be both from a crossReferenceTable or a crossReferenceStream */
class CrossReferenceSource {
    /** @var list<CrossReferenceSection> Where the first is the newest incremental update and the last one is the oldest */
    private readonly array $crossReferenceSections;

    /** @no-named-arguments */
    public function __construct(
        CrossReferenceSection... $crossReferenceSections,
    ) {
        $this->crossReferenceSections = $crossReferenceSections;
    }

    public function getCrossReferenceEntry(int $objNumber): CrossReferenceEntryInUseObject|CrossReferenceEntryCompressed|null {
        foreach ($this->crossReferenceSections as $crossReferenceSection) {
            $crossReferenceEntry = $crossReferenceSection->getCrossReferenceEntry($objNumber);
            if ($crossReferenceEntry !== null) {
                return $crossReferenceEntry;
            }
        }

        return null;
    }

    public function getReferenceForKey(DictionaryKey $dictionaryKey): ?ReferenceValue {
        return $this->getValueForKey($dictionaryKey, ReferenceValue::class);
    }

    /**
     * @template T of DictionaryValue|NameValue|Dictionary
     * @param class-string<T> $valueType
     * @return T
     */
    public function getValueForKey(DictionaryKey $dictionaryKey, string $valueType): DictionaryValue|Dictionary|NameValue|null {
        foreach ($this->crossReferenceSections as $crossReferenceSection) {
            $valueForKey = $crossReferenceSection->dictionary->getValueForKey($dictionaryKey, $valueType);
            if ($valueForKey !== null) {
                return $valueForKey;
            }
        }

        return null;
    }

    public function getFirstId(): string {
        $value = $this->getValueForKey(DictionaryKey::ID, ArrayValue::class)->value[0]
            ?? throw new ParseFailureException('Unable to retrieve first id from cross reference source');
        if (!is_string($value)) {
            throw new ParseFailureException('First id is not a string');
        }

        if (!str_starts_with($value, '<') || !str_ends_with($value, '>')) {
            throw new ParseFailureException('Unsupported first id format, expected "<hex>"');
        }

        $firstId = hex2bin(substr($value, 1, -1));
        if ($firstId === false) {
            throw new ParseFailureException('Unable to retrieve binary value from first id');
        }

        return $firstId;
    }
}
