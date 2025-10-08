<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Object\Item\CompressedObject;

use Override;
use PrinsFrank\PdfParser\Document\Dictionary\Dictionary;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryKey\DictionaryKey;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryParser;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Integer\IntegerValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Reference\ReferenceValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Reference\ReferenceValueArray;
use PrinsFrank\PdfParser\Document\Document;
use PrinsFrank\PdfParser\Document\Object\Item\ObjectItem;
use PrinsFrank\PdfParser\Document\Object\Item\UncompressedObject\UncompressedObject;
use PrinsFrank\PdfParser\Exception\InvalidArgumentException;
use PrinsFrank\PdfParser\Exception\ParseFailureException;
use PrinsFrank\PdfParser\Exception\RuntimeException;
use PrinsFrank\PdfParser\Stream\FileStream;
use PrinsFrank\PdfParser\Stream\InMemoryStream;
use PrinsFrank\PdfParser\Stream\Stream;

/** @api */
class CompressedObject implements ObjectItem {
    private readonly Dictionary $dictionary;

    public function __construct(
        public readonly int $objectNumber,
        public readonly UncompressedObject $storedInObject,
        public readonly int $startByteOffsetInDecodedStream,
        public readonly ?int $endByteOffsetInDecodedStream,
    ) {
        if ($this->endByteOffsetInDecodedStream !== null && $this->startByteOffsetInDecodedStream > $this->endByteOffsetInDecodedStream) {
            throw new InvalidArgumentException(sprintf('Start offset %d should be before end offset %d', $this->startByteOffsetInDecodedStream, $this->endByteOffsetInDecodedStream));
        }
    }

    #[Override]
    public function getDictionary(Document $document): Dictionary {
        if (isset($this->dictionary)) {
            return $this->dictionary;
        }

        $objectContent = trim($this->getContent($document)->toString());
        if ($objectContent === '' || !str_starts_with($objectContent, '<<') || !str_ends_with($objectContent, '>>')) {
            return $this->dictionary = new Dictionary();
        }

        $inMemoryStream = new InMemoryStream($objectContent);
        return $this->dictionary = DictionaryParser::parse($inMemoryStream, 0, $inMemoryStream->getSizeInBytes());
    }

    #[Override]
    public function getContent(Document $document): Stream {
        $first = $this->storedInObject->getDictionary($document)->getValueForKey(DictionaryKey::FIRST, IntegerValue::class)
            ?? throw new RuntimeException('Expected a dictionary entry for "First", none found');

        $content = substr(
            $this->storedInObject->getContent($document)->toString(),
            $first->value + $this->startByteOffsetInDecodedStream,
            $this->endByteOffsetInDecodedStream !== null ? $this->endByteOffsetInDecodedStream - $this->startByteOffsetInDecodedStream : null
        );

        if (str_starts_with($content, '[') && str_ends_with($content, ']') && ($referenceValueArray = ReferenceValueArray::fromValue($content)) !== null) {
            $content = implode(
                '',
                array_map(
                    fn (ReferenceValue $referenceValue) => ($document->getObject($referenceValue->objectNumber) ?? throw new ParseFailureException())
                        ->getStream()
                        ->toString(),
                    $referenceValueArray->referenceValues,
                )
            );
        }

        return FileStream::fromString($content);
    }
}
