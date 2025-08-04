<?php
declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\CrossReference;

use PrinsFrank\PdfParser\Document\CrossReference\Source\CrossReferenceSource;
use PrinsFrank\PdfParser\Document\CrossReference\Stream\CrossReferenceStreamParser;
use PrinsFrank\PdfParser\Document\CrossReference\Table\CrossReferenceTableParser;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryKey\DictionaryKey;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Integer\IntegerValue;
use PrinsFrank\PdfParser\Document\Generic\Marker;
use PrinsFrank\PdfParser\Exception\ParseFailureException;
use PrinsFrank\PdfParser\Exception\PdfParserException;
use PrinsFrank\PdfParser\Stream\Stream;

/** @internal */
class CrossReferenceSourceParser {
    /** @throws PdfParserException */
    public static function parse(Stream $stream): CrossReferenceSource {
        $eofMarkerPos = $stream->lastPos(Marker::EOF, 0)
            ?? throw new ParseFailureException(sprintf('Unable to locate marker %s', Marker::EOF->value));
        $startXrefMarkerPos = $stream->lastPos(Marker::START_XREF, $stream->getSizeInBytes() - $eofMarkerPos)
            ?? throw new ParseFailureException(sprintf('Unable to locate marker %s', Marker::START_XREF->value));
        $startByteOffset = $stream->getStartOfNextLine($startXrefMarkerPos, $stream->getSizeInBytes())
            ?? throw new ParseFailureException('Expected a carriage return or line feed after startxref marker, none found');
        $endByteOffset = $stream->getEndOfCurrentLine($startByteOffset, $stream->getSizeInBytes())
            ?? throw new ParseFailureException('Expected a carriage return or line feed after the byte offset, none found');

        $byteOffsetLastCrossReferenceSection = trim($stream->read($startByteOffset, $endByteOffset - $startByteOffset));
        if ($byteOffsetLastCrossReferenceSection !== (string)(int) $byteOffsetLastCrossReferenceSection) {
            throw new ParseFailureException(sprintf('Invalid byte offset last crossReference section "%s", "%s"', $byteOffsetLastCrossReferenceSection, $stream->read($startXrefMarkerPos, $stream->getSizeInBytes() - $startXrefMarkerPos)));
        }

        $byteOffsetLastCrossReferenceSection = (int) $byteOffsetLastCrossReferenceSection;
        if ($byteOffsetLastCrossReferenceSection > $stream->getSizeInBytes()) {
            throw new ParseFailureException(sprintf('Invalid byte offset: position of last crossReference section %d is greater than total size of stream %d. Should this be %d?', $byteOffsetLastCrossReferenceSection, $stream->getSizeInBytes(), $stream->lastPos(Marker::XREF, $stream->getSizeInBytes() - $startXrefMarkerPos) ?? $stream->lastPos(Marker::OBJ, $stream->getSizeInBytes() - $startXrefMarkerPos)));
        }

        $eolPosByteOffset = $stream->getEndOfCurrentLine($byteOffsetLastCrossReferenceSection, $stream->getSizeInBytes())
            ?? throw new ParseFailureException('Expected a newline after byte offset for last cross reference stream');

        $crossReferenceType = self::getCrossReferenceType($stream, $byteOffsetLastCrossReferenceSection, $eolPosByteOffset);
        if ($crossReferenceType === null) { // Try to recover from an invalid byte offset crossReference section
            $lastPosXrefSection = $stream->lastPos(Marker::XREF, $stream->getSizeInBytes() - $startXrefMarkerPos);
            $lastPosObject = $stream->lastPos(Marker::OBJ, $stream->getSizeInBytes() - $startXrefMarkerPos);
            if ($lastPosXrefSection === null && $lastPosObject === null) {
                throw new ParseFailureException(sprintf('Unable to determine cross reference type for start line "%s" of crossReference source, and no other crossReference table or stream was found.', $stream->read($byteOffsetLastCrossReferenceSection, $eolPosByteOffset - $byteOffsetLastCrossReferenceSection)));
            }

            $lastPossibleXrefSectionPos = $lastPosObject === null ? $lastPosXrefSection : ($lastPosXrefSection === null ? $lastPosObject : max($lastPosXrefSection, $lastPosObject));
            $eolStartXrefSectionPos = $stream->getEndOfCurrentLine($lastPossibleXrefSectionPos, $stream->getSizeInBytes())
                ?? throw new ParseFailureException(sprintf('Unable to determine cross reference type for start line "%s" of crossReference source, and no other crossReference table or stream was found.', $stream->read($startByteOffset, $endByteOffset - $startByteOffset)));
            $crossReferenceType = self::getCrossReferenceType($stream, $lastPossibleXrefSectionPos, $eolStartXrefSectionPos)
                ?? throw new ParseFailureException(sprintf('Unable to determine cross reference type for start line "%s" of crossReference source, and no other crossReference table or stream was found.', $stream->read($startByteOffset, $endByteOffset - $startByteOffset)));
        }

        $endCrossReferenceSection = $crossReferenceType === CrossReferenceType::Table
            ? ($stream->firstPos(Marker::START_XREF, $eolPosByteOffset, $stream->getSizeInBytes()) ?? throw new ParseFailureException(sprintf('Unable to locate marker %s', Marker::START_XREF->value)))
            : ($stream->firstPos(Marker::END_OBJ, $eolPosByteOffset, $stream->getSizeInBytes()) ?? throw new ParseFailureException(sprintf('Unable to locate marker %s', Marker::END_OBJ->value)));
        $currentCrossReferenceSection = $crossReferenceType === CrossReferenceType::Table
            ? CrossReferenceTableParser::parse($stream, $eolPosByteOffset, $endCrossReferenceSection - $eolPosByteOffset)
            : CrossReferenceStreamParser::parse($stream, $eolPosByteOffset, $endCrossReferenceSection - $eolPosByteOffset);
        $crossReferenceSections = [$currentCrossReferenceSection];
        while (($previous = $currentCrossReferenceSection->dictionary->getValueForKey(DictionaryKey::PREV, IntegerValue::class)) !== null && $previous->value !== 0) {
            $eolPosByteOffset = $stream->getEndOfCurrentLine($previous->value + 1, $stream->getSizeInBytes())
                ?? throw new ParseFailureException('Expected a newline after byte offset for cross reference stream');
            $endCrossReferenceSection = $crossReferenceType === CrossReferenceType::Table
                ? $stream->firstPos(Marker::START_XREF, $eolPosByteOffset, $stream->getSizeInBytes()) ?? throw new ParseFailureException('Unable to locate startxref')
                : $stream->firstPos(Marker::END_OBJ, $eolPosByteOffset, $stream->getSizeInBytes()) ?? throw new ParseFailureException('Unable to locate endobj');

            $currentCrossReferenceSection = $crossReferenceType === CrossReferenceType::Table
                ? CrossReferenceTableParser::parse($stream, $eolPosByteOffset, $endCrossReferenceSection - $eolPosByteOffset)
                : CrossReferenceStreamParser::parse($stream, $eolPosByteOffset, $endCrossReferenceSection - $eolPosByteOffset);
            $crossReferenceSections[] = $currentCrossReferenceSection;
        }

        return new CrossReferenceSource(... $crossReferenceSections);
    }

    private static function getCrossReferenceType(Stream $stream, int $byteOffsetLastCrossReferenceSection, int $byteOffsetEndOfCurrentLine): ?CrossReferenceType {
        $startCrossReferenceContent = trim($stream->read($byteOffsetLastCrossReferenceSection, $byteOffsetEndOfCurrentLine - $byteOffsetLastCrossReferenceSection));
        if ($startCrossReferenceContent === Marker::XREF->value) {
            return CrossReferenceType::Table;
        }

        if (preg_match('/^[0-9]*\s*[0-9]*\s*obj$/', $startCrossReferenceContent) === 1) {
            return CrossReferenceType::Stream;
        }

        return null;
    }
}
