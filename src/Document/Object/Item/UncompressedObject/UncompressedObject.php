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
use PrinsFrank\PdfParser\Document\Security\EncryptionContext;
use PrinsFrank\PdfParser\Exception\InvalidArgumentException;
use PrinsFrank\PdfParser\Exception\ParseFailureException;
use PrinsFrank\PdfParser\Stream\FileStream;
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
    public function getDictionary(Document $document): Dictionary {
        if (isset($this->dictionary)) {
            return $this->dictionary;
        }

        $startDictionaryPos = $document->stream->firstPos(DelimiterCharacter::LESS_THAN_SIGN, $this->startOffset, $this->endOffset);
        if ($startDictionaryPos === null) {
            return $this->dictionary = new Dictionary();
        }

        $endDictionaryPos = $document->stream->firstPos(Marker::STREAM, $startDictionaryPos, $this->endOffset)
            ?? $document->stream->lastPos(Marker::END_OBJ, $document->stream->getSizeInBytes() - $this->endOffset)
            ?? throw new ParseFailureException('Unable to locate start of stream or end of current object');

        return $this->dictionary = DictionaryParser::parse($this->getEncryptionContext(), $document->stream, $startDictionaryPos, $endDictionaryPos - $startDictionaryPos);
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
        if ($dictionary->getType($document) !== TypeNameValue::OBJ_STM) {
            throw new ParseFailureException('Unable to get stream data from item that is not a stream');
        }

        return $this->byteOffsets = CompressedObjectByteOffsetParser::parse(
            $document->stream,
            $this->startOffset,
            $this->endOffset,
            $dictionary,
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
    public function getContent(Document $document): Stream {
        if (($startStreamPos = $document->stream->getStartNextLineAfter(Marker::STREAM, $this->startOffset, $this->endOffset)) !== null
            && ($endStreamPos = $document->stream->lastPos(Marker::END_STREAM, $document->stream->getSizeInBytes() - $this->endOffset)) !== null) {
            return CompressedObjectContentParser::parseBinary(
                $document,
                $startStreamPos,
                ($document->stream->getEndOfCurrentLine($endStreamPos - 1, $this->endOffset)
                    ?? throw new ParseFailureException(sprintf('Unable to locate marker %s', WhitespaceCharacter::LINE_FEED->value))) - $startStreamPos,
                $this->getDictionary($document),
            );
        }

        $nextLineAfterStartObj = $document->stream->getStartNextLineAfter(Marker::OBJ, $this->startOffset, $this->endOffset)
            ?? throw new ParseFailureException(sprintf('Unable to locate newline after marker %s', Marker::OBJ->value));
        $endObjPos = $document->stream->lastPos(Marker::END_OBJ, $document->stream->getSizeInBytes() - $this->endOffset)
            ?? throw new ParseFailureException(sprintf('Unable to locate marker %s', Marker::END_OBJ->value));
        $eolObjContent = $document->stream->getEndOfCurrentLine($endObjPos - 2, $this->endOffset)
            ?? throw new ParseFailureException(sprintf('Unable to locate newline after marker %s', Marker::END_OBJ->value));

        return FileStream::fromString(
            $document->stream->read(
                $nextLineAfterStartObj,
                $eolObjContent - $nextLineAfterStartObj,
            ),
        );
    }
}
