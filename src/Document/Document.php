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
use PrinsFrank\PdfParser\Document\Object\Decorator\DecoratedObject;
use PrinsFrank\PdfParser\Document\Object\Decorator\DecoratedObjectFactory;
use PrinsFrank\PdfParser\Document\Object\Decorator\InformationDictionary;
use PrinsFrank\PdfParser\Document\Object\Decorator\Page;
use PrinsFrank\PdfParser\Document\Object\Item\UncompressedObject\UncompressedObject;
use PrinsFrank\PdfParser\Document\Object\Item\UncompressedObject\UncompressedObjectParser;
use PrinsFrank\PdfParser\Document\Version\Version;
use PrinsFrank\PdfParser\Exception\NotImplementedException;
use PrinsFrank\PdfParser\Exception\ParseFailureException;
use PrinsFrank\PdfParser\Exception\PdfParserException;
use PrinsFrank\PdfParser\Exception\RuntimeException;
use PrinsFrank\PdfParser\Stream\Stream;

/** @api */
final class Document {
    /** @var list<Page> */
    private readonly array $pages;

    /** @var array<int, DecoratedObject|null> */
    private array $objectCache = [];

    public function __construct(
        public readonly Stream               $stream,
        public readonly Version              $version,
        public readonly CrossReferenceSource $crossReferenceSource,
    ) {
        if ($this->isEncrypted()) {
            throw new NotImplementedException('Encrypted documents are not supported yet');
        }
    }

    /** @throws PdfParserException */
    public function getInformationDictionary(): ?InformationDictionary {
        $infoReference = $this->crossReferenceSource->getReferenceForKey(DictionaryKey::INFO);
        if ($infoReference === null) {
            return null;
        }

        return $this->getObject($infoReference->objectNumber, InformationDictionary::class);
    }

    public function isEncrypted(): bool {
        return $this->crossReferenceSource->getReferenceForKey(DictionaryKey::ENCRYPT) !== null;
    }

    /** @throws PdfParserException */
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
     * @throws PdfParserException
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

        throw new ParseFailureException(sprintf('Dictionary value with key "%s" is of type "%s", expected referencevalue(array)', $dictionaryKey->name, $dictionaryValueType ?? 'null'));
    }

    /**
     * @template T of DecoratedObject
     * @param class-string<T>|null $expectedDecoratorFQN
     * @throws PdfParserException
     * @return ($expectedDecoratorFQN is null ? DecoratedObject : T)
     */
    public function getObject(int $objectNumber, ?string $expectedDecoratorFQN = null): ?DecoratedObject {
        if (array_key_exists($objectNumber, $this->objectCache)) {
            return $this->objectCache[$objectNumber];
        }

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

            $objectItem = $parentObject->objectItem->getCompressedObject($objectNumber, $this);
        } else {
            $objectItem = UncompressedObjectParser::parseObject($crossReferenceEntry, $objectNumber, $this->stream);
        }

        return $this->objectCache[$objectNumber] = DecoratedObjectFactory::forItem($objectItem, $this, $expectedDecoratorFQN);
    }

    /** @throws PdfParserException */
    public function getPage(int $pageNumber): ?Page {
        return $this->getPages()[$pageNumber - 1] ?? null;
    }

    /** @throws PdfParserException */
    public function getNumberOfPages(): int {
        return count($this->getPages());
    }

    /**
     * @throws PdfParserException
     * @return list<Page>
     */
    public function getPages(): array {
        return $this->pages ??= $this->getCatalog()
            ->getPagesRoot()
            ->getPageItems();
    }

    /**
     * @param ?string $pageSeparator an optional string to put between text of different pages
     * @throws PdfParserException
     */
    public function getText(?string $pageSeparator = null): string {
        $text = '';
        foreach ($this->getPages() as $page) {
            $text .= ($pageSeparator !== null ? $pageSeparator : '')
                . $page->getText();
        }

        return $text;
    }
}
