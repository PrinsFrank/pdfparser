<?php
declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Object\Item\CompressedObject\CompressedObjectContent;

use PrinsFrank\PdfParser\Document\Dictionary\Dictionary;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryKey\DictionaryKey;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Array\ArrayValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Name\FilterNameValue;
use PrinsFrank\PdfParser\Exception\ParseFailureException;
use PrinsFrank\PdfParser\Exception\PdfParserException;
use PrinsFrank\PdfParser\Exception\RuntimeException;
use PrinsFrank\PdfParser\Stream\Stream;

class CompressedObjectContentParser {
    /** @throws PdfParserException */
    public static function parse(Stream $stream, int $startPos, int $nrOfBytes, Dictionary $dictionary): string {
        $streamContent = $stream->read($startPos, $nrOfBytes);
        if (($filterType = $dictionary->getTypeForKey(DictionaryKey::FILTER)) === FilterNameValue::class) {
            $streamContent = ($dictionary->getValueForKey(DictionaryKey::FILTER, FilterNameValue::class) ?? throw new ParseFailureException())
                ->decode($streamContent, $dictionary->getValueForKey(DictionaryKey::DECODE_PARMS, Dictionary::class));
        } elseif ($filterType === ArrayValue::class) {
            foreach ($dictionary->getValueForKey(DictionaryKey::FILTER, ArrayValue::class)->value ?? throw new ParseFailureException() as $filterValue) {
                if (is_string($filterValue) === false) {
                    throw new ParseFailureException();
                }

                $streamContent = FilterNameValue::from(ltrim($filterValue, '/'))
                    ->decode($streamContent, $dictionary->getValueForKey(DictionaryKey::DECODE_PARMS, Dictionary::class));
            }
        } elseif ($filterType !== null) {
            throw new RuntimeException(sprintf('Expected filter to be a FilterNameValue or ArrayValue, got %s', $filterType));
        }

        return $streamContent;
    }
}
