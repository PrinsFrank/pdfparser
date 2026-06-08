<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Object\Item\UncompressedObject;

use PrinsFrank\PdfParser\Document\CrossReference\Source\Section\SubSection\Entry\CrossReferenceEntryInUseObject;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryKey\DictionaryKey;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryParser;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Integer\IntegerValue;
use PrinsFrank\PdfParser\Document\Document;
use PrinsFrank\PdfParser\Document\Generic\Character\WhitespaceCharacter;
use PrinsFrank\PdfParser\Document\Generic\Marker;
use PrinsFrank\PdfParser\Exception\ParseFailureException;

/** @internal */
class UncompressedObjectParser {
    public static function parseObject(CrossReferenceEntryInUseObject $crossReferenceEntry, int $objectNumber, Document $document): UncompressedObject {
        $startObj = $document->stream->firstPos(Marker::OBJ, $crossReferenceEntry->byteOffsetInDecodedStream, $document->stream->getSizeInBytes())
            ?? throw new ParseFailureException('Unable to locate start of object');
        $endObj = $document->stream->firstPos(Marker::END_OBJ, $startObj, $document->stream->getSizeInBytes())
            ?? throw new ParseFailureException('Unable to locate end of object');
        if (($startStream = $document->stream->getStartNextLineAfter(Marker::STREAM, $startObj, $endObj)) !== null) {
            $dictionary = DictionaryParser::parse(null, $document->stream, $startObj, $startStream - $startObj);
            $length = $dictionary->getValueForKey($document, DictionaryKey::LENGTH, IntegerValue::class)?->value;
            $endStream = $document->stream->firstPos(Marker::END_STREAM, $startStream + ($length ?? 0), $document->stream->getSizeInBytes())
                ?? throw new ParseFailureException('Unable to locate end of stream');
            $endObj = $document->stream->firstPos(Marker::END_OBJ, $endStream, $document->stream->getSizeInBytes())
                ?? throw new ParseFailureException('Unable to locate end of object');
        }

        $objHeader = $document->stream->read($crossReferenceEntry->byteOffsetInDecodedStream, $startObj + Marker::OBJ->length() - $crossReferenceEntry->byteOffsetInDecodedStream);
        $objHeaderParts = explode(WhitespaceCharacter::SPACE->value, str_replace([WhitespaceCharacter::LINE_FEED->value], ' ', trim($objHeader)));
        if (count($objHeaderParts) !== 3 || (int) $objHeaderParts[0] !== $objectNumber || (int) $objHeaderParts[1] !== $crossReferenceEntry->generationNumber || $objHeaderParts[2] !== Marker::OBJ->value) {
            throw new ParseFailureException(sprintf('Expected "%d %d %s" on first line, got "%s"', $objectNumber, $crossReferenceEntry->generationNumber, Marker::OBJ->value, $objHeader));
        }

        return new UncompressedObject(
            $document,
            $objectNumber,
            $crossReferenceEntry->generationNumber,
            $crossReferenceEntry->byteOffsetInDecodedStream,
            $endObj + Marker::END_OBJ->length(),
        );
    }
}
