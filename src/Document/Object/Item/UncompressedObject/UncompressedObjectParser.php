<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Object\Item\UncompressedObject;

use PrinsFrank\PdfParser\Document\CrossReference\Source\Section\SubSection\Entry\CrossReferenceEntryInUseObject;
use PrinsFrank\PdfParser\Document\Generic\Character\WhitespaceCharacter;
use PrinsFrank\PdfParser\Document\Generic\Marker;
use PrinsFrank\PdfParser\Exception\ParseFailureException;
use PrinsFrank\PdfParser\Stream\Stream;

/** @internal */
class UncompressedObjectParser {
    public static function parseObject(
        CrossReferenceEntryInUseObject $crossReferenceEntry,
        int                            $objectNumber,
        Stream                 $stream,
    ): UncompressedObject {
        $endObj = $stream->firstPos(Marker::END_OBJ, $crossReferenceEntry->byteOffsetInDecodedStream, $stream->getSizeInBytes()) ?? throw new ParseFailureException('Unable to locate end of object');
        $startObj = $stream->firstPos(Marker::OBJ, $crossReferenceEntry->byteOffsetInDecodedStream, $endObj) ?? throw new ParseFailureException('Unable to locate start of object');
        $objHeader = $stream->read($crossReferenceEntry->byteOffsetInDecodedStream, $startObj + Marker::OBJ->length() - $crossReferenceEntry->byteOffsetInDecodedStream);
        $objHeaderParts = explode(WhitespaceCharacter::SPACE->value, str_replace([WhitespaceCharacter::LINE_FEED->value], ' ', trim($objHeader)));
        if (count($objHeaderParts) !== 3 || (int) $objHeaderParts[0] !== $objectNumber || (int) $objHeaderParts[1] !== $crossReferenceEntry->generationNumber || $objHeaderParts[2] !== Marker::OBJ->value) {
            throw new ParseFailureException(sprintf('Expected "%d %d %s" on first line, got "%s"', $objectNumber, $crossReferenceEntry->generationNumber, Marker::OBJ->value, $objHeader));
        }

        return new UncompressedObject(
            $objectNumber,
            $crossReferenceEntry->generationNumber,
            $crossReferenceEntry->byteOffsetInDecodedStream,
            $endObj + Marker::END_OBJ->length(),
        );
    }
}
