<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Object\UncompressedObject;

use Override;
use PrinsFrank\PdfParser\Document\Dictionary\Dictionary;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryKey\DictionaryKey;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryParser;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\DictionaryValueType\Name\TypeNameValue;
use PrinsFrank\PdfParser\Document\Generic\Character\DelimiterCharacter;
use PrinsFrank\PdfParser\Document\Generic\Character\WhitespaceCharacter;
use PrinsFrank\PdfParser\Document\Generic\Marker;
use PrinsFrank\PdfParser\Document\Object\CompressedObject\CompressedObject;
use PrinsFrank\PdfParser\Document\Object\CompressedObject\CompressedObjectByteOffsetParser;
use PrinsFrank\PdfParser\Document\Object\CompressedObject\CompressedObjectByteOffsets;
use PrinsFrank\PdfParser\Document\Object\CompressedObject\CompressedObjectContent\CompressedObjectContentParser;
use PrinsFrank\PdfParser\Document\Object\ObjectItem;
use PrinsFrank\PdfParser\Exception\InvalidArgumentException;
use PrinsFrank\PdfParser\Exception\MarkerNotFoundException;
use PrinsFrank\PdfParser\Exception\ParseFailureException;
use PrinsFrank\PdfParser\Stream;

class UncompressedObject implements ObjectItem {
    /** @phpstan-ignore property.uninitializedReadonly */
    private readonly ?Dictionary $dictionary;

    /** @phpstan-ignore property.uninitializedReadonly */
    private readonly CompressedObjectByteOffsets $byteOffsets;

    public function __construct(
        public readonly int $objectNumber,
        public readonly int $generationNumber,
        public readonly int $startOffset,
        public readonly int $endOffset,
    ) {
    }

    #[Override]
    public function getDictionary(Stream $stream): ?Dictionary {
        if (isset($this->dictionary) === true) {
            return $this->dictionary;
        }

        $startDictionaryPos = $stream->firstPos(DelimiterCharacter::LESS_THAN_SIGN, $this->startOffset, $this->endOffset);
        if ($startDictionaryPos === null) {
            /** @phpstan-ignore property.readOnlyAssignNotInConstructor */
            return $this->dictionary = null;
        }

        $endDictionaryPos = $stream->lastPos(DelimiterCharacter::GREATER_THAN_SIGN, $stream->getSizeInBytes() - $this->endOffset);
        if ($endDictionaryPos === null || $endDictionaryPos < $startDictionaryPos) {
            throw new ParseFailureException(sprintf('Couldn\'t find the end of the dictionary in "%s"', $stream->read($startDictionaryPos, $stream->getSizeInBytes() - $this->endOffset)));
        }

        /** @phpstan-ignore property.readOnlyAssignNotInConstructor */
        return $this->dictionary = DictionaryParser::parse($stream, $startDictionaryPos, $endDictionaryPos - $startDictionaryPos + strlen(DelimiterCharacter::GREATER_THAN_SIGN->value . DelimiterCharacter::GREATER_THAN_SIGN->value));
    }

    public function getCompressedObject(int $objectNumber, Stream $stream): CompressedObject {
        $byteOffsets = $this->getByteOffsets($stream);
        $startByteOffset = $byteOffsets->getRelativeByteOffsetForObject($objectNumber)
            ?? throw new InvalidArgumentException('Compressed object does not exist in this uncompressed object');

        return new CompressedObject(
            $objectNumber,
            $this,
            $startByteOffset,
            $byteOffsets->getNextRelativeByteOffset($startByteOffset),
        );
    }

    public function getByteOffsets(Stream $stream): CompressedObjectByteOffsets {
        if (isset($this->byteOffsets)) {
            return $this->byteOffsets;
        }

        $dictionary = $this->getDictionary($stream);
        if ($dictionary?->getValueForKey(DictionaryKey::TYPE, TypeNameValue::class) !== TypeNameValue::OBJ_STM) {
            throw new ParseFailureException('Unable to get stream data from item that is not a stream');
        }

        /** @phpstan-ignore property.readOnlyAssignNotInConstructor */
        return $this->byteOffsets = CompressedObjectByteOffsetParser::parse(
            $stream,
            $this->startOffset,
            $this->endOffset,
            $dictionary
        );
    }

    public function getStreamContent(Stream $stream): string {
        $startStreamPos = $stream->getStartNextLineAfter(Marker::STREAM, $this->startOffset, $this->endOffset)
            ?? throw new MarkerNotFoundException(Marker::STREAM->value);
        $endStreamPos = $stream->firstPos(Marker::END_STREAM, $startStreamPos, $this->endOffset)
            ?? throw new MarkerNotFoundException(Marker::END_STREAM->value);
        $eolPos = $stream->getEndOfCurrentLine($endStreamPos - 1, $this->endOffset)
            ?? throw new MarkerNotFoundException(WhitespaceCharacter::LINE_FEED->value);

        return CompressedObjectContentParser::parse(
            $stream,
            $startStreamPos,
            $eolPos - $startStreamPos,
            $this->getDictionary($stream) ?? throw new ParseFailureException('Unable to get dictionary for uncompressed object'),
        );
    }
}