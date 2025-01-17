<?php
declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document;

use PrinsFrank\PdfParser\Document\CrossReference\Source\CrossReferenceSource;
use PrinsFrank\PdfParser\Document\CrossReference\Source\Section\SubSection\Entry\CrossReferenceEntryCompressed;
use PrinsFrank\PdfParser\Document\Dictionary\Dictionary;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryKey\DictionaryKey;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Reference\ReferenceValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Reference\ReferenceValueArray;
use PrinsFrank\PdfParser\Document\Object\Decorator\Catalog;
use PrinsFrank\PdfParser\Document\Object\Decorator\DecoratedObjectFactory;
use PrinsFrank\PdfParser\Document\Object\Decorator\DecoratedObject;
use PrinsFrank\PdfParser\Document\Object\Decorator\InformationDictionary;
use PrinsFrank\PdfParser\Document\Object\Decorator\Page;
use PrinsFrank\PdfParser\Document\Object\Item\UncompressedObject\UncompressedObject;
use PrinsFrank\PdfParser\Document\Object\Item\UncompressedObject\UncompressedObjectParser;
use PrinsFrank\PdfParser\Document\Version\Version;
use PrinsFrank\PdfParser\Exception\ParseFailureException;
use PrinsFrank\PdfParser\Exception\RuntimeException;
use PrinsFrank\PdfParser\Stream;

final class Document {
    /** @var list<Page> */
    private readonly array $pages;

    public function __construct(
        public readonly Stream               $stream,
        public readonly Version              $version,
        public readonly CrossReferenceSource $crossReferenceSource,
    ) {
    }

    public function getInformationDictionary(): ?InformationDictionary {
        $infoReference = $this->crossReferenceSource->getReferenceForKey(DictionaryKey::INFO);
        if ($infoReference === null) {
            return null;
        }

        return $this->getObject($infoReference->objectNumber, InformationDictionary::class);
    }

    public function getCatalog(): Catalog {
        $rootReference = $this->crossReferenceSource->getReferenceForKey(DictionaryKey::ROOT)
            ?? throw new ParseFailureException('Unable to locate root for document.');
        $catalog = $this->getObject($rootReference->objectNumber, Catalog::class)
            ?? throw new ParseFailureException(sprintf('Document references object %d as root, but object couln\'t be located', $rootReference->objectNumber));
        if (!$catalog instanceof Catalog) {
            throw new RuntimeException('Catalog should be a catalog item');
        }

        return $catalog;
    }

    /**
     * @template T of DecoratedObject
     * @param class-string<T>|null $expectedDecoratorFQN
     * @return ($expectedDecoratorFQN is null ? list<DecoratedObject> : list<T>)
     */
    public function getObjectsByDictionaryKey(Dictionary $dictionary, DictionaryKey $dictionaryKey, ?string $expectedDecoratorFQN = null): array {
        $dictionaryValueType = $dictionary->getTypeForKey($dictionaryKey);
        if ($dictionaryValueType === ReferenceValue::class) {
            return [$this->getObject($dictionary->getValueForKey($dictionaryKey, ReferenceValue::class)->objectNumber ?? throw new ParseFailureException(), $expectedDecoratorFQN) ?? throw new ParseFailureException()];
        } elseif ($dictionaryValueType === ReferenceValueArray::class) {
            return array_map(
                fn (ReferenceValue $referenceValue) => $this->getObject($referenceValue->objectNumber, $expectedDecoratorFQN) ?? throw new ParseFailureException(),
                $dictionary->getValueForKey($dictionaryKey, ReferenceValueArray::class)->referenceValues ?? throw new ParseFailureException(),
            );
        }

        throw new ParseFailureException(sprintf('Dictionary value with key "%s" is of type "%s", expected referencevalue(array)', $dictionaryKey->name, $dictionaryValueType));
    }

    /**
     * @template T of DecoratedObject
     * @param class-string<T>|null $expectedDecoratorFQN
     * @return ($expectedDecoratorFQN is null ? DecoratedObject : T)
     */
    public function getObject(int $objectNumber, ?string $expectedDecoratorFQN = null): ?DecoratedObject {
        $crossReferenceEntry = $this->crossReferenceSource->getCrossReferenceEntry($objectNumber);
        if ($crossReferenceEntry === null) {
            return null;
        }

        if ($crossReferenceEntry instanceof CrossReferenceEntryCompressed) {
            $parentObject = $this->getObject($crossReferenceEntry->storedInStreamWithObjectNumber)
                ?? throw new RuntimeException(sprintf('Parent object for %d with number %d doesn\'t exist', $objectNumber, $crossReferenceEntry->storedInStreamWithObjectNumber));

            if (!$parentObject->objectItem instanceof UncompressedObject) {
                throw new RuntimeException('Parents for stream items shouldn\'t be stream items themselves');
            }

            return DecoratedObjectFactory::forItem(
                $parentObject->objectItem->getCompressedObject($objectNumber, $this->stream),
                $this,
                $expectedDecoratorFQN,
            );
        }

        return DecoratedObjectFactory::forItem(
            UncompressedObjectParser::parseObject($crossReferenceEntry, $objectNumber, $this->stream, ),
            $this,
            $expectedDecoratorFQN,
        );
    }

    public function getPage(int $pageNumber): ?Page {
        return $this->getPages()[$pageNumber - 1] ?? null;
    }

    public function getNumberOfPages(): int {
        return count($this->getPages());
    }

    /** @return list<Page> */
    public function getPages(): array {
        return $this->pages ??= $this->getCatalog()
            ->getPagesRoot()
            ->getPageItems();
    }

    /** @param ?string $pageSeparator an optional string to put between text of different pages */
    public function getText(?string $pageSeparator = null): string {
        $text = '';
        foreach ($this->getPages() as $page) {
            $text .= ($pageSeparator !== null ? $pageSeparator : '')
                . $page->getText();
        }

        return $text;
    }
}
