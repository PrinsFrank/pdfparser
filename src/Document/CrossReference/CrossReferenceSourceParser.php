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
use PrinsFrank\PdfParser\Stream\Stream;

class CrossReferenceSourceParser {
    public static function parse(Stream $stream): CrossReferenceSource {
        $eofMarkerPos = $stream->lastPos(Marker::EOF, 0)
            ?? throw new ParseFailureException(sprintf('Unable to locate marker %s', Marker::EOF->value));
        $startXrefMarkerPos = $stream->lastPos(Marker::START_XREF, $stream->getSizeInBytes() - $eofMarkerPos)
            ?? throw new ParseFailureException(sprintf('Unable to locate marker %s', Marker::START_XREF->value));
        $startByteOffset = $stream->getStartOfNextLine($startXrefMarkerPos, $stream->getSizeInBytes())
            ?? throw new ParseFailureException('Expected a carriage return or line feed after startxref marker, none found');
        $endByteOffset = $stream->getEndOfCurrentLine($startByteOffset, $stream->getSizeInBytes())
            ?? throw new ParseFailureException('Expected a carriage return or line feed after the byte offset, none found');

        $byteOffsetLastCrossReferenceSection = $stream->read($startByteOffset, $endByteOffset - $startByteOffset);
        if ($byteOffsetLastCrossReferenceSection !== (string)(int) $byteOffsetLastCrossReferenceSection) {
            throw new ParseFailureException(sprintf('Invalid byte offset last crossReference section "%s", "%s"', $byteOffsetLastCrossReferenceSection, $stream->read($startXrefMarkerPos, $stream->getSizeInBytes() - $startXrefMarkerPos)));
        }

        $byteOffsetLastCrossReferenceSection = (int) $byteOffsetLastCrossReferenceSection;
        if ($byteOffsetLastCrossReferenceSection > $stream->getSizeInBytes()) {
            throw new ParseFailureException(sprintf('Invalid byte offset: position of last crossReference section %d is greater than total size of stream %d. Should this be %d?', $byteOffsetLastCrossReferenceSection, $stream->getSizeInBytes(), $stream->lastPos(Marker::XREF, $stream->getSizeInBytes() - $startXrefMarkerPos) ?? $stream->lastPos(Marker::OBJ, $stream->getSizeInBytes() - $startXrefMarkerPos)));
        }

        $eolPosByteOffset = $stream->getEndOfCurrentLine($byteOffsetLastCrossReferenceSection, $stream->getSizeInBytes())
            ?? throw new ParseFailureException('Expected a newline after byte offset for last cross reference stream');

        $isTable = $stream->read($byteOffsetLastCrossReferenceSection, $eolPosByteOffset - $byteOffsetLastCrossReferenceSection) === Marker::XREF->value;
        $endCrossReferenceSection = $isTable
            ? ($stream->firstPos(Marker::START_XREF, $eolPosByteOffset, $stream->getSizeInBytes()) ?? throw new ParseFailureException(sprintf('Unable to locate marker %s', Marker::START_XREF->value)))
            : ($stream->firstPos(Marker::END_OBJ, $eolPosByteOffset, $stream->getSizeInBytes()) ?? throw new ParseFailureException(sprintf('Unable to locate marker %s', Marker::END_OBJ->value)));
        $currentCrossReferenceSection = $isTable
            ? CrossReferenceTableParser::parse($stream, $eolPosByteOffset, $endCrossReferenceSection - $eolPosByteOffset)
            : CrossReferenceStreamParser::parse($stream, $eolPosByteOffset, $endCrossReferenceSection - $eolPosByteOffset);
        $crossReferenceSections = [$currentCrossReferenceSection];
        while (($previous = $currentCrossReferenceSection->dictionary->getValueForKey(DictionaryKey::PREV, IntegerValue::class)) !== null && $previous->value !== 0) {
            $eolPosByteOffset = $stream->getEndOfCurrentLine($previous->value + 1, $stream->getSizeInBytes())
                ?? throw new ParseFailureException('Expected a newline after byte offset for cross reference stream');
            $endCrossReferenceSection = $isTable
                ? $stream->firstPos(Marker::START_XREF, $eolPosByteOffset, $stream->getSizeInBytes()) ?? throw new ParseFailureException('Unable to locate startxref')
                : $stream->firstPos(Marker::END_OBJ, $eolPosByteOffset, $stream->getSizeInBytes()) ?? throw new ParseFailureException('Unable to locate endobj');

            $currentCrossReferenceSection = $isTable
                ? CrossReferenceTableParser::parse($stream, $eolPosByteOffset, $endCrossReferenceSection - $eolPosByteOffset)
                : CrossReferenceStreamParser::parse($stream, $eolPosByteOffset, $endCrossReferenceSection - $eolPosByteOffset);
            $crossReferenceSections[] = $currentCrossReferenceSection;
        }

        return new CrossReferenceSource(... $crossReferenceSections);
    }
}
