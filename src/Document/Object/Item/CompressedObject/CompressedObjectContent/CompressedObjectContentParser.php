<?php
declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Object\Item\CompressedObject\CompressedObjectContent;

use PrinsFrank\PdfParser\Document\Dictionary\Dictionary;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryKey\DictionaryKey;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Array\ArrayValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Name\FilterNameValue;
use PrinsFrank\PdfParser\Document\Document;
use PrinsFrank\PdfParser\Exception\ParseFailureException;
use PrinsFrank\PdfParser\Exception\PdfParserException;
use PrinsFrank\PdfParser\Exception\RuntimeException;
use PrinsFrank\PdfParser\Stream\Stream;

/** @internal */
class CompressedObjectContentParser {
    /**
     * @param Stream|Document $context the document (or stream during parsing) that is currently being parsed
     * @throws PdfParserException
     * @return string in binary format
     */
    public static function parseBinary(Stream|Document $context, int $startPos, int $nrOfBytes, Dictionary $dictionary): string {
        $binaryStreamContent = ($context instanceof Document ? $context->stream : $context)->read($startPos, $nrOfBytes);
        if (($filterType = $dictionary->getTypeForKey(DictionaryKey::FILTER)) === FilterNameValue::class) {
            $binaryStreamContent = ($dictionary->getValueForKey(DictionaryKey::FILTER, FilterNameValue::class) ?? throw new ParseFailureException())
                ->decodeBinary($binaryStreamContent, $dictionary, ($context instanceof Document ? $context : null));
        } elseif ($filterType === ArrayValue::class) {
            foreach ($dictionary->getValueForKey(DictionaryKey::FILTER, ArrayValue::class)->value ?? throw new ParseFailureException() as $filterValue) {
                if (is_string($filterValue) === false || ($filter = FilterNameValue::tryFrom(ltrim($filterValue, '/'))) === null) {
                    throw new ParseFailureException();
                }

                $binaryStreamContent = $filter
                    ->decodeBinary($binaryStreamContent, $dictionary, ($context instanceof Document ? $context : null));
            }
        } elseif ($filterType !== null) {
            throw new RuntimeException(sprintf('Expected filter to be a FilterNameValue or ArrayValue, got %s', $filterType));
        }

        return $binaryStreamContent;
    }
}
