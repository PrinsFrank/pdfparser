<?php
declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Object\ObjectStream;

use PrinsFrank\PdfParser\Document\Dictionary\Dictionary;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryKey\DictionaryKey;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\DictionaryValueType\Name\FilterNameValue;
use PrinsFrank\PdfParser\Document\Generic\Marker;
use PrinsFrank\PdfParser\Exception\ParseFailureException;

class ObjectStreamParser
{
    /**
     * @throws ParseFailureException
     */
    public static function parse(string $content, Dictionary $dictionary): ?string
    {
        $startStream = strpos($content, Marker::START_STREAM->value);
        $endStream = strpos($content, Marker::END_STREAM->value);
        if ($startStream === false || $endStream === false) {
            return null;
        }

        $stream = substr($content, $startStream + strlen(Marker::START_STREAM->value), $endStream - $startStream - strlen(Marker::START_STREAM->value));

        $streamFilter = $dictionary->getEntryWithKey(DictionaryKey::FILTER)?->value;
        if ($streamFilter instanceof FilterNameValue) {
            $stream = $streamFilter::decode($streamFilter, $stream);
        }

        return $stream;
    }
}
