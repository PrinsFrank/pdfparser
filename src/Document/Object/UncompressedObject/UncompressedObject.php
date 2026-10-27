<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Object\UncompressedObject;

use Override;
use PrinsFrank\PdfParser\Document\Dictionary\Decorator\DecoratedDictionary;
use PrinsFrank\PdfParser\Document\Dictionary\Decorator\GenericDictionary;
use PrinsFrank\PdfParser\Document\Dictionary\Dictionary;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryParser;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Name\TypeNameValue;
use PrinsFrank\PdfParser\Document\Document;
use PrinsFrank\PdfParser\Document\Generic\Character\DelimiterCharacter;
use PrinsFrank\PdfParser\Document\Generic\Character\WhitespaceCharacter;
use PrinsFrank\PdfParser\Document\Generic\Marker;
use PrinsFrank\PdfParser\Document\Object\CompressedObject\CompressedObject;
use PrinsFrank\PdfParser\Document\Object\CompressedObject\CompressedObjectByteOffsetParser;
use PrinsFrank\PdfParser\Document\Object\CompressedObject\CompressedObjectByteOffsets;
use PrinsFrank\PdfParser\Document\Object\CompressedObject\CompressedObjectContent\CompressedObjectContentParser;
use PrinsFrank\PdfParser\Document\Object\ObjectItem;
use PrinsFrank\PdfParser\Document\Security\EncryptionContext;
use PrinsFrank\PdfParser\Exception\InvalidArgumentException;
use PrinsFrank\PdfParser\Exception\ParseFailureException;
use PrinsFrank\PdfParser\Stream\FileStream;
use PrinsFrank\PdfParser\Stream\InMemoryStream;
use PrinsFrank\PdfParser\Stream\Stream;

/** @api */
readonly class UncompressedObject implements ObjectItem {
    private Dictionary $dictionary;
    private CompressedObjectByteOffsets $byteOffsets;

    public function __construct(
        public Document $document,
        public int $objectNumber,
        public int $generationNumber,
        public int $startOffset,
        public int $endOffset,
    ) {}

    #[Override]
    public function getDictionary(string $decoratorFQN = GenericDictionary::class): DecoratedDictionary {
        if (isset($this->dictionary)) {
            return new $decoratorFQN($this->dictionary);
        }

        $startDictionaryPos = $this->document->stream->firstPos(DelimiterCharacter::LESS_THAN_SIGN, $this->startOffset, $this->endOffset);
        if ($startDictionaryPos === null) {
            return new $decoratorFQN($this->dictionary = new Dictionary($this));
        }

        $endDictionaryPos = $this->document->stream->firstPos(Marker::STREAM, $startDictionaryPos, $this->endOffset)
            ?? $this->document->stream->lastPos(Marker::END_OBJ, $this->document->stream->getSizeInBytes() - $this->endOffset)
            ?? throw new ParseFailureException('Unable to locate start of stream or end of current object');

        return new $decoratorFQN($this->dictionary = DictionaryParser::parse($this, $this->getEncryptionContext(), $this->document->stream, $startDictionaryPos, $endDictionaryPos - $startDictionaryPos));
    }

    public function getCompressedObject(int $objectNumber): CompressedObject {
        $byteOffsets = $this->getByteOffsets();
        $startByteOffset = $byteOffsets->getRelativeByteOffsetForObject($objectNumber)
            ?? throw new InvalidArgumentException('Compressed object does not exist in this uncompressed object');

        return new CompressedObject(
            $this->document,
            $objectNumber,
            $this,
            $startByteOffset,
            $byteOffsets->getNextRelativeByteOffset($startByteOffset),
        );
    }

    public function getByteOffsets(): CompressedObjectByteOffsets {
        if (isset($this->byteOffsets)) {
            return $this->byteOffsets;
        }

        $dictionary = $this->getDictionary();
        if ($dictionary->getType() !== TypeNameValue::OBJ_STM) {
            throw new ParseFailureException('Unable to get stream data from item that is not a stream');
        }

        return $this->byteOffsets = CompressedObjectByteOffsetParser::parse(
            $this->document->stream,
            $this->startOffset,
            $this->endOffset,
            $dictionary->dictionary,
        );
    }

    public function getEncryptionContext(): ?EncryptionContext {
        if (isset($this->document->fileEncryptionKey) === false) { // isset instead of null check because while retrieving fileEncryptionKey we might need to parse dictionaries already
            return null;
        }

        return new EncryptionContext(
            $this->document->fileEncryptionKey,
            $this->objectNumber,
            $this->generationNumber,
        );
    }

    #[Override]
    public function getContent(): Stream {
        if (($startStreamPos = $this->document->stream->getStartNextLineAfter(Marker::STREAM, $this->startOffset, $this->endOffset)) !== null
            && ($endStreamPos = $this->document->stream->lastPos(Marker::END_STREAM, $this->document->stream->getSizeInBytes() - $this->endOffset)) !== null) {
            if ($startStreamPos === $endStreamPos
                || $startStreamPos === ($eolEndStreamPos = $this->document->stream->getEndOfCurrentLine($endStreamPos - 1, $this->endOffset))) {
                return new InMemoryStream('');
            }

            return CompressedObjectContentParser::parseBinary(
                $this->getEncryptionContext(),
                $this->document,
                $startStreamPos,
                ($eolEndStreamPos ?? throw new ParseFailureException(sprintf('Unable to locate marker %s', WhitespaceCharacter::LINE_FEED->value))) - $startStreamPos,
                $this->getDictionary()->dictionary,
            );
        }

        $startObjPos = $this->document->stream->firstPos(Marker::OBJ, $this->startOffset, $this->endOffset)
            ?? throw new ParseFailureException(sprintf('Unable to locate marker %s', Marker::OBJ->value));
        $endObjPos = $this->document->stream->lastPos(Marker::END_OBJ, $this->document->stream->getSizeInBytes() - $this->endOffset)
            ?? throw new ParseFailureException(sprintf('Unable to locate marker %s', Marker::END_OBJ->value));

        return FileStream::fromString(
            $this->document->stream->read(
                $startObjPos + Marker::OBJ->length(),
                $endObjPos - ($startObjPos + Marker::OBJ->length()),
            ),
        );
    }
}
