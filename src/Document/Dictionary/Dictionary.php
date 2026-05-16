<?php
declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Dictionary;

use PrinsFrank\PdfParser\Document\Dictionary\DictionaryEntry\DictionaryEntry;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryKey\DictionaryKey;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryKey\ExtendedDictionaryKey;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Array\DictionaryArrayValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\DictionaryValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Name\NameValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Name\SubtypeNameValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Name\TypeNameValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Reference\ReferenceValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Reference\ReferenceValueArray;
use PrinsFrank\PdfParser\Document\Document;
use PrinsFrank\PdfParser\Document\Object\Decorator\DecoratedObject;
use PrinsFrank\PdfParser\Exception\InvalidArgumentException;
use PrinsFrank\PdfParser\Exception\ParseFailureException;
use PrinsFrank\PdfParser\Exception\RuntimeException;

readonly class Dictionary {
    /** @var array<DictionaryEntry> */
    public array $dictionaryEntries;

    /** @no-named-arguments */
    public function __construct(
        DictionaryEntry... $dictionaryEntries,
    ) {
        $this->dictionaryEntries = $dictionaryEntries;
    }

    /**
     * @template T of DictionaryValue|NameValue|Dictionary
     * @param class-string<T> $expectedValueType
     * @return T
     */
    public function getValueForKey(?Document $document, DictionaryKey|ExtendedDictionaryKey $dictionaryKey, string $expectedValueType): DictionaryValue|Dictionary|NameValue|null {
        foreach ($this->dictionaryEntries as $dictionaryEntry) {
            if (($dictionaryKey instanceof DictionaryKey && $dictionaryEntry->key === $dictionaryKey) === false
                && ($dictionaryKey instanceof ExtendedDictionaryKey && $dictionaryEntry->key instanceof ExtendedDictionaryKey && $dictionaryEntry->key->value === $dictionaryKey->value) === false) {
                continue;
            }

            $value = $dictionaryEntry->value;
            if ($value instanceof Dictionary && $expectedValueType !== Dictionary::class) {
                $value = $value->getValueForKey($document, $dictionaryKey, $expectedValueType)
                    ?? throw new InvalidArgumentException('Value type is dictionary but subdictionary not found');
            }

            if ($value instanceof ReferenceValue && $document !== null && $expectedValueType !== ReferenceValue::class && is_a($expectedValueType, DictionaryValue::class, true)) {
                $value = $expectedValueType::fromValue(
                    $content = trim(($document->getObject($value->objectNumber) ?? throw new InvalidArgumentException(sprintf('Object with nr %d not found', $value->objectNumber)))
                        ->getStream()->toString()),
                ) ?? throw new ParseFailureException(sprintf('Unable to parse content "%s" of referenced object %d as %s', $content, $value->objectNumber, $expectedValueType));
            }

            if (is_a($value, $expectedValueType) === false) {
                throw new InvalidArgumentException(sprintf('Expected value with value %s to be of type %s, got %s', $dictionaryKey->value, $expectedValueType, get_class($value)));
            }

            return $value;
        }

        return null;
    }

    /** @return class-string<DictionaryValue|NameValue|Dictionary> */
    public function getTypeForKey(DictionaryKey $dictionaryKey): ?string {
        foreach ($this->dictionaryEntries as $dictionaryEntry) {
            if ($dictionaryEntry->key === $dictionaryKey) {
                return $dictionaryEntry->value::class;
            }
        }

        return null;
    }

    public function getSubDictionary(?Document $document, DictionaryKey $dictionaryKey): ?Dictionary {
        $subDictionaryType = $this->getTypeForKey($dictionaryKey);
        if ($subDictionaryType === null) {
            return null;
        }

        if ($subDictionaryType === Dictionary::class) {
            return $this->getValueForKey($document, $dictionaryKey, Dictionary::class) ?? throw new RuntimeException();
        }

        if ($subDictionaryType === DictionaryArrayValue::class) {
            return ($this->getValueForKey($document, $dictionaryKey, DictionaryArrayValue::class) ?? throw new RuntimeException())->toSingleDictionary();
        }

        if ($subDictionaryType === ReferenceValue::class) {
            if ($document === null) {
                throw new ParseFailureException('Document is required to get subDictionary for reference');
            }

            return ($this->getObjectForReference($document, $dictionaryKey) ?? throw new ParseFailureException())
                ->getDictionary();
        }

        throw new ParseFailureException(sprintf('Invalid type "%s" for subDictionary with key %s', $subDictionaryType, $dictionaryKey->name));
    }

    /**
     * @template T of DecoratedObject
     * @param class-string<T>|null $expectedDecoratorFQN
     * @return ($expectedDecoratorFQN is null ? DecoratedObject : T)
     */
    public function getObjectForReference(Document $document, DictionaryKey|ExtendedDictionaryKey $dictionaryKey, ?string $expectedDecoratorFQN = null): ?DecoratedObject {
        $reference = $this->getValueForKey($document, $dictionaryKey, ReferenceValue::class);
        if ($reference === null) {
            return null;
        }

        return $document->getObject($reference->objectNumber, $expectedDecoratorFQN)
            ?? throw new ParseFailureException();
    }

    /**
     * @template T of DecoratedObject
     * @param class-string<T>|null $expectedDecoratorFQN
     * @return ($expectedDecoratorFQN is null ? list<DecoratedObject> : list<T>)
     */
    public function getObjectsForReference(Document $document, DictionaryKey|ExtendedDictionaryKey $dictionaryKey, ?string $expectedDecoratorFQN = null): array {
        $references = $this->getValueForKey($document, $dictionaryKey, ReferenceValueArray::class);
        if ($references === null) {
            return [];
        }

        $objects = [];
        foreach ($references->referenceValues as $referenceValue) {
            $objects[] = $document->getObject($referenceValue->objectNumber, $expectedDecoratorFQN)
                ?? throw new ParseFailureException();
        }

        return $objects;
    }

    public function getType(?Document $document): ?TypeNameValue {
        return $this->getValueForKey($document, DictionaryKey::TYPE, TypeNameValue::class);
    }

    public function getSubType(?Document $document): ?SubtypeNameValue {
        return $this->getValueForKey($document, DictionaryKey::SUBTYPE, SubtypeNameValue::class);
    }
}
