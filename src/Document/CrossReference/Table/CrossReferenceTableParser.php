<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\CrossReference\Table;

use PrinsFrank\PdfParser\Document\CrossReference\Source\Section\CrossReferenceSection;
use PrinsFrank\PdfParser\Document\CrossReference\Source\Section\SubSection\CrossReferenceSubSection;
use PrinsFrank\PdfParser\Document\CrossReference\Source\Section\SubSection\Entry\CrossReferenceEntryFreeObject;
use PrinsFrank\PdfParser\Document\CrossReference\Source\Section\SubSection\Entry\CrossReferenceEntryInUseObject;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryParser;
use PrinsFrank\PdfParser\Document\Generic\Character\WhitespaceCharacter;
use PrinsFrank\PdfParser\Document\Generic\Marker;
use PrinsFrank\PdfParser\Exception\InvalidCrossReferenceLineException;
use PrinsFrank\PdfParser\Exception\ParseFailureException;
use PrinsFrank\PdfParser\Stream\Stream;

class CrossReferenceTableParser {
    public static function parse(Stream $stream, int $startPos, int $nrOfBytes): CrossReferenceSection {
        $startTrailerPos = $stream->firstPos(Marker::TRAILER, $startPos, $startPos + $nrOfBytes)
            ?? throw new ParseFailureException('Unable to locate trailer for crossReferenceTable');
        $startDictionaryPos = $stream->firstPos(WhitespaceCharacter::LINE_FEED, $startTrailerPos, $startPos + $nrOfBytes)
            ?? $stream->firstPos(WhitespaceCharacter::CARRIAGE_RETURN, $startTrailerPos, $startPos + $nrOfBytes)
            ?? throw new ParseFailureException(sprintf('Expected a newline after %s, got none', Marker::TRAILER->value));
        $dictionary = DictionaryParser::parse($stream, $startDictionaryPos, $nrOfBytes - ($startDictionaryPos - $startPos));

        $objectNumber = $nrOfEntries = null;
        $crossReferenceSubSections = $crossReferenceEntries = [];
        $content = trim($stream->read($startPos, $startDictionaryPos - $startPos - strlen(Marker::TRAILER->value . PHP_EOL)));
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
                    $crossReferenceEntries[] = match (CrossReferenceTableInUseOrFree::from(trim($sections[2]))) {
                        CrossReferenceTableInUseOrFree::IN_USE => new CrossReferenceEntryInUseObject((int) $sections[0], (int) $sections[1]),
                        CrossReferenceTableInUseOrFree::FREE => new CrossReferenceEntryFreeObject((int) $sections[0], (int) $sections[1]),
                    };
                    break;
                default:
                    throw new InvalidCrossReferenceLineException(sprintf('Invalid line "%s", 2 or 3 sections expected, %d found', substr(trim($line), 0, 30), count($sections)));
            }
        }

        if ($objectNumber !== null && $nrOfEntries !== null) {
            $crossReferenceSubSections[] = new CrossReferenceSubSection($objectNumber, $nrOfEntries, ... $crossReferenceEntries);
        }

        return new CrossReferenceSection($dictionary, ... $crossReferenceSubSections);
    }
}
