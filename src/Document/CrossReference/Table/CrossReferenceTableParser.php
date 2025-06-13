<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\CrossReference\Table;

use PrinsFrank\PdfParser\Document\CrossReference\Source\Section\CrossReferenceSection;
use PrinsFrank\PdfParser\Document\CrossReference\Source\Section\SubSection\CrossReferenceSubSection;
use PrinsFrank\PdfParser\Document\CrossReference\Source\Section\SubSection\Entry\CrossReferenceEntryFreeObject;
use PrinsFrank\PdfParser\Document\CrossReference\Source\Section\SubSection\Entry\CrossReferenceEntryInUseObject;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryParser;
use PrinsFrank\PdfParser\Document\Generic\Character\WhitespaceCharacter;
use PrinsFrank\PdfParser\Document\Generic\Marker;
use PrinsFrank\PdfParser\Exception\ParseFailureException;
use PrinsFrank\PdfParser\Exception\PdfParserException;
use PrinsFrank\PdfParser\Stream\Stream;

/** @internal */
class CrossReferenceTableParser {
    /** @throws PdfParserException */
    public static function parse(Stream $stream, int $startPos, int $nrOfBytes): CrossReferenceSection {
        $startTrailerPos = $stream->firstPos(Marker::TRAILER, $startPos, $startPos + $nrOfBytes)
            ?? throw new ParseFailureException('Unable to locate trailer for crossReferenceTable');
        $dictionary = DictionaryParser::parse($stream, $startTrailerPos + Marker::TRAILER->length(), $nrOfBytes - ($startTrailerPos + Marker::TRAILER->length() - $startPos));

        $objectNumber = $nrOfEntries = null;
        $crossReferenceSubSections = $crossReferenceEntries = [];
        $content = trim($stream->read($startPos, $startTrailerPos - $startPos));
        $content = str_replace([WhitespaceCharacter::CARRIAGE_RETURN->value, WhitespaceCharacter::LINE_FEED->value . WhitespaceCharacter::LINE_FEED->value], WhitespaceCharacter::LINE_FEED->value, $content);
        foreach (explode(WhitespaceCharacter::LINE_FEED->value, $content) as $line) {
            $sections = explode(WhitespaceCharacter::SPACE->value, trim($line));
            switch (count($sections)) {
                case 2:
                    if ($objectNumber !== null && $nrOfEntries !== null) {
                        $crossReferenceSubSections[] = new CrossReferenceSubSection($objectNumber, $nrOfEntries, ... $crossReferenceEntries); // Use previous objectNr and nrOfEntries
                    }
                    $crossReferenceEntries = [];
                    $objectNumber = (int) $sections[0];
                    $nrOfEntries = (int) $sections[1];
                    break;
                case 3:
                    $crossReferenceEntries[] = match (CrossReferenceTableInUseOrFree::tryFrom(trim($sections[2]))) {
                        CrossReferenceTableInUseOrFree::IN_USE => new CrossReferenceEntryInUseObject((int) $sections[0], (int) $sections[1]),
                        CrossReferenceTableInUseOrFree::FREE => new CrossReferenceEntryFreeObject((int) $sections[0], (int) $sections[1]),
                        null => throw new ParseFailureException(sprintf('Unrecognized crossReference table record type %s', trim($sections[2])))
                    };
                    break;
                default:
                    throw new ParseFailureException(sprintf('Invalid line "%s", 2 or 3 sections expected, %d found', substr(trim($line), 0, 30), count($sections)));
            }
        }

        if ($objectNumber !== null && $nrOfEntries !== null) {
            $crossReferenceSubSections[] = new CrossReferenceSubSection($objectNumber, $nrOfEntries, ... $crossReferenceEntries);
        }

        return new CrossReferenceSection($dictionary, ... $crossReferenceSubSections);
    }
}
