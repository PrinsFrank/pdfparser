<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Object\Item\CompressedObject;

use PrinsFrank\PdfParser\Document\Dictionary\Dictionary;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryKey\DictionaryKey;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Integer\IntegerValue;
use PrinsFrank\PdfParser\Document\Generic\Character\WhitespaceCharacter;
use PrinsFrank\PdfParser\Document\Generic\Marker;
use PrinsFrank\PdfParser\Document\Generic\Parsing\InfiniteBuffer;
use PrinsFrank\PdfParser\Document\Object\Item\CompressedObject\CompressedObjectContent\CompressedObjectContentParser;
use PrinsFrank\PdfParser\Exception\ParseFailureException;
use PrinsFrank\PdfParser\Exception\PdfParserException;
use PrinsFrank\PdfParser\Exception\RuntimeException;
use PrinsFrank\PdfParser\Stream\Stream;

class CompressedObjectByteOffsetParser {
    /** @throws PdfParserException */
    public static function parse(Stream $stream, int $startOffsetObject, int $endOffsetObject, Dictionary $dictionary): CompressedObjectByteOffsets {
        $startStreamPos = $stream->getStartNextLineAfter(Marker::STREAM, $startOffsetObject, $endOffsetObject)
            ?? throw new ParseFailureException(sprintf('Unable to locate marker %s', Marker::STREAM->value));
        $endStreamPos = $stream->firstPos(Marker::END_STREAM, $startStreamPos, $endOffsetObject)
            ?? throw new ParseFailureException(sprintf('Unable to locate marker %s', Marker::END_STREAM->value));
        $eolPos = $stream->getEndOfCurrentLine($endStreamPos - 1, $endOffsetObject)
            ?? throw new ParseFailureException(sprintf('Unable to locate marker %s', WhitespaceCharacter::LINE_FEED->value));
        $content = CompressedObjectContentParser::parse($stream, $startStreamPos, $eolPos - $startStreamPos, $dictionary);
        $first = $dictionary->getValueForKey(DictionaryKey::FIRST, IntegerValue::class)
            ?? throw new RuntimeException('Expected a dictionary entry for "First", none found');
        $buffer = new InfiniteBuffer();
        $previousObjectNumber = null;
        $byteOffsets = [];
        foreach (str_split(substr($content, 0, $first->value * 2), 2) as $char) {
            $decodedChar = mb_chr((int) hexdec($char));
            if (WhitespaceCharacter::tryFrom($decodedChar) !== null) {
                $numberInBuffer = $buffer->__toString();
                if ($numberInBuffer !== (string)(int) $numberInBuffer) {
                    throw new ParseFailureException(sprintf('Number "%s" in buffer is not a valid number', $numberInBuffer));
                }

                $numberInBuffer = (int) $numberInBuffer;
                if ($previousObjectNumber !== null) {
                    $byteOffsets[$previousObjectNumber] = $numberInBuffer;
                    $previousObjectNumber = null;
                } else {
                    $previousObjectNumber = $numberInBuffer;
                }

                $buffer->flush();
                continue;
            }

            $buffer->addChar($decodedChar);
        }

        return new CompressedObjectByteOffsets($byteOffsets);
    }
}
