<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\CrossReference\Stream;

use PrinsFrank\PdfParser\Document\CrossReference\Source\Section\CrossReferenceSection;
use PrinsFrank\PdfParser\Document\CrossReference\Source\Section\SubSection\CrossReferenceSubSection;
use PrinsFrank\PdfParser\Document\CrossReference\Source\Section\SubSection\Entry\CrossReferenceEntryCompressed;
use PrinsFrank\PdfParser\Document\CrossReference\Source\Section\SubSection\Entry\CrossReferenceEntryFreeObject;
use PrinsFrank\PdfParser\Document\CrossReference\Source\Section\SubSection\Entry\CrossReferenceEntryInUseObject;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryKey\DictionaryKey;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryParser;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Array\ArrayValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Array\WValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Integer\IntegerValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Name\TypeNameValue;
use PrinsFrank\PdfParser\Document\Generic\Marker;
use PrinsFrank\PdfParser\Document\Object\Item\CompressedObject\CompressedObjectContent\CompressedObjectContentParser;
use PrinsFrank\PdfParser\Exception\ParseFailureException;
use PrinsFrank\PdfParser\Exception\PdfParserException;
use PrinsFrank\PdfParser\Stream\Stream;

/** @internal */
class CrossReferenceStreamParser {
    private const HEX_CHARS_IN_BYTE = 2;

    /**
     * @phpstan-assert int<0, max> $startPos
     * @phpstan-assert int<1, max> $nrOfBytes
     *
     * @throws PdfParserException
     */
    public static function parse(Stream $stream, int $startPos, int $nrOfBytes): CrossReferenceSection {
        $dictionary = DictionaryParser::parse($stream, $startPos, $nrOfBytes);
        if ($dictionary->getType() !== TypeNameValue::X_REF) {
            throw new ParseFailureException('Expected stream of type xref');
        }

        $wValue = $dictionary->getValueForKey(DictionaryKey::W, WValue::class)
            ?? throw new ParseFailureException('Cross reference streams should have a dictionary entry for "W"');
        $startStream = $stream->getStartNextLineAfter(Marker::STREAM, $startPos, $startPos + $nrOfBytes)
            ?? throw new ParseFailureException(sprintf('Unable to locate marker %s', Marker::STREAM->value));

        $endStream = $stream->firstPos(Marker::END_STREAM, $startStream, $startPos + $nrOfBytes);
        if ($endStream === null || $endStream > ($startPos + $nrOfBytes)) {
            throw new ParseFailureException(sprintf('Expected end of stream content marked by %s, none found', Marker::END_STREAM->value));
        }

        $entries = [];
        $hexContent = bin2hex(CompressedObjectContentParser::parseBinary($stream, $startStream, $endStream - $startStream - 1, $dictionary));
        foreach (str_split($hexContent, $wValue->getTotalLengthInBytes() * self::HEX_CHARS_IN_BYTE) as $referenceRow) {
            $field1 = hexdec(substr($referenceRow, 0, $wValue->lengthRecord1InBytes * self::HEX_CHARS_IN_BYTE));
            $field2 = hexdec(substr($referenceRow, $wValue->lengthRecord1InBytes * self::HEX_CHARS_IN_BYTE, $wValue->lengthRecord2InBytes * self::HEX_CHARS_IN_BYTE));
            $field3 = hexdec(substr($referenceRow, ($wValue->lengthRecord1InBytes + $wValue->lengthRecord2InBytes) * self::HEX_CHARS_IN_BYTE, $wValue->lengthRecord3InBytes * self::HEX_CHARS_IN_BYTE));
            if (!is_int($field1) || !is_int($field2) || !is_int($field3)) {
                throw new ParseFailureException(sprintf('Field 1, 2 and 3 in cross reference entries should be int, got %s, %s and %s', gettype($field1), gettype($field2), gettype($field3)));
            }

            $entries[] = match (CrossReferenceStreamType::tryFrom($field1)) {
                CrossReferenceStreamType::LINKED_LIST_FREE_OBJECT => new CrossReferenceEntryFreeObject($field2, $field3),
                CrossReferenceStreamType::UNCOMPRESSED_OBJECT => new CrossReferenceEntryInUseObject($field2, $field3),
                CrossReferenceStreamType::COMPRESSED_OBJECT => new CrossReferenceEntryCompressed($field2, $field3),
                null => throw new ParseFailureException(sprintf('Unrecognized CrossReferenceStream type "%s"', $field1)),
            };
        }

        /** @var list<int> $startObjNrOfItemsArray where all even items are the start object number and all odd items are the number of objects */
        $startObjNrOfItemsArray = $dictionary->getValueForKey(DictionaryKey::INDEX, ArrayValue::class)->value
            ?? [0, $dictionary->getValueForKey(DictionaryKey::SIZE, IntegerValue::class)->value ?? throw new ParseFailureException('Cross reference streams should have either an index or a size, neither was found')];

        $crossReferenceSubSections = [];
        foreach (array_chunk($startObjNrOfItemsArray, 2) as $startNrNrOfObjects) {
            /** @phpstan-ignore offsetAccess.notFound, offsetAccess.notFound */
            $crossReferenceSubSections[] = new CrossReferenceSubSection($startNrNrOfObjects[0], $startNrNrOfObjects[1], ... array_slice($entries, 0, $startNrNrOfObjects[1]));
            $entries = array_slice($entries, $startNrNrOfObjects[1]);
        }

        return new CrossReferenceSection($dictionary, ... $crossReferenceSubSections);
    }
}
