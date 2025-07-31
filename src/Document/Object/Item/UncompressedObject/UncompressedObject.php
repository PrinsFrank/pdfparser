<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Object\Item\UncompressedObject;

use Override;
use PrinsFrank\PdfParser\Document\Dictionary\Dictionary;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryParser;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Name\TypeNameValue;
use PrinsFrank\PdfParser\Document\Document;
use PrinsFrank\PdfParser\Document\Generic\Character\DelimiterCharacter;
use PrinsFrank\PdfParser\Document\Generic\Character\WhitespaceCharacter;
use PrinsFrank\PdfParser\Document\Generic\Marker;
use PrinsFrank\PdfParser\Document\Object\Item\CompressedObject\CompressedObject;
use PrinsFrank\PdfParser\Document\Object\Item\CompressedObject\CompressedObjectByteOffsetParser;
use PrinsFrank\PdfParser\Document\Object\Item\CompressedObject\CompressedObjectByteOffsets;
use PrinsFrank\PdfParser\Document\Object\Item\CompressedObject\CompressedObjectContent\CompressedObjectContentParser;
use PrinsFrank\PdfParser\Document\Object\Item\ObjectItem;
use PrinsFrank\PdfParser\Exception\InvalidArgumentException;
use PrinsFrank\PdfParser\Exception\ParseFailureException;

/** @api */
class UncompressedObject implements ObjectItem {
    private readonly Dictionary $dictionary;
    private readonly CompressedObjectByteOffsets $byteOffsets;

    public function __construct(
        public readonly int $objectNumber,
        public readonly int $generationNumber,
        public readonly int $startOffset,
        public readonly int $endOffset,
    ) {
    }

    #[Override]
    public function getDictionary(Document $document): Dictionary {
        if (isset($this->dictionary)) {
            return $this->dictionary;
        }

        $startDictionaryPos = $document->stream->firstPos(DelimiterCharacter::LESS_THAN_SIGN, $this->startOffset, $this->endOffset);
        if ($startDictionaryPos === null) {
            return $this->dictionary = new Dictionary();
        }

        $endDictionaryPos = $document->stream->firstPos(Marker::STREAM, $startDictionaryPos, $this->endOffset)
            ?? $document->stream->firstPos(Marker::END_OBJ, $startDictionaryPos, $this->endOffset)
            ?? throw new ParseFailureException('Unable to locate start of stream or end of current object');

        return $this->dictionary = DictionaryParser::parse($document->stream, $startDictionaryPos, $endDictionaryPos - $startDictionaryPos);
    }

    public function getCompressedObject(int $objectNumber, Document $document): CompressedObject {
        $byteOffsets = $this->getByteOffsets($document);
        $startByteOffset = $byteOffsets->getRelativeByteOffsetForObject($objectNumber)
            ?? throw new InvalidArgumentException('Compressed object does not exist in this uncompressed object');

        return new CompressedObject(
            $objectNumber,
            $this,
            $startByteOffset,
            $byteOffsets->getNextRelativeByteOffset($startByteOffset),
        );
    }

    public function getByteOffsets(Document $document): CompressedObjectByteOffsets {
        if (isset($this->byteOffsets)) {
            return $this->byteOffsets;
        }

        $dictionary = $this->getDictionary($document);
        if ($dictionary->getType() !== TypeNameValue::OBJ_STM) {
            throw new ParseFailureException('Unable to get stream data from item that is not a stream');
        }

        return $this->byteOffsets = CompressedObjectByteOffsetParser::parse(
            $document->stream,
            $this->startOffset,
            $this->endOffset,
            $dictionary
        );
    }

    #[Override]
    public function getContent(Document $document): string {
        if (($startStreamPos = $document->stream->getStartNextLineAfter(Marker::STREAM, $this->startOffset, $this->endOffset)) === null
            || ($endStreamPos = $document->stream->firstPos(Marker::END_STREAM, $startStreamPos, $this->endOffset)) === null) {
            $startMarkerLen = strlen(sprintf('%d %d obj', $this->objectNumber, $this->generationNumber));

            return $document->stream->read(
                $this->startOffset + $startMarkerLen,
                $this->endOffset - $this->startOffset - $startMarkerLen - strlen(Marker::END_STREAM->value),
            );
        }

        return CompressedObjectContentParser::parseBinary(
            $document,
            $startStreamPos,
            ($document->stream->getEndOfCurrentLine($endStreamPos - 1, $this->endOffset)
            ?? throw new ParseFailureException(sprintf('Unable to locate marker %s', WhitespaceCharacter::LINE_FEED->value))) - $startStreamPos,
            $this->getDictionary($document),
        );
    }
}
