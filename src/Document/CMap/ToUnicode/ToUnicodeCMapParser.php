<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\CMap\ToUnicode;

use PrinsFrank\PdfParser\Exception\ParseFailureException;
use PrinsFrank\PdfParser\Exception\PdfParserException;
use PrinsFrank\PdfParser\Stream\Stream;

/** @internal */
class ToUnicodeCMapParser {
    /** @throws PdfParserException */
    public static function parse(Stream $stream, int $startOffset, int $nrOfBytes): ToUnicodeCMap {
        $beginCodeSpaceRangePos = $stream->firstPos(ToUnicodeCMapOperator::BeginCodeSpaceRange, $startOffset, $startOffset + $nrOfBytes)
            ?? throw new ParseFailureException(sprintf('Missing %s', ToUnicodeCMapOperator::BeginCodeSpaceRange->value));
        $beginCodeSpaceRangePos += strlen(ToUnicodeCMapOperator::BeginCodeSpaceRange->value);
        $endCodeSpaceRangePos = $stream->firstPos(ToUnicodeCMapOperator::EndCodeSpaceRange, $beginCodeSpaceRangePos, $startOffset + $nrOfBytes)
            ?? throw new ParseFailureException();
        $codeSpaceRangeSectionString = $stream->read($beginCodeSpaceRangePos, $endCodeSpaceRangePos - $beginCodeSpaceRangePos);
        $codeSpaceRanges = [];
        $byteSize = null;
        foreach (explode("\n", $codeSpaceRangeSectionString) as $codeSpaceRangeSectionStringLine) {
            if (trim($codeSpaceRangeSectionStringLine) === '') {
                continue;
            }

            if (preg_match('/^\s*<(?P<start>[0-9a-fA-F ]+)>\s*<(?P<end>[0-9a-fA-F ]+)>\s*$/', $codeSpaceRangeSectionStringLine, $matchesSpaceRange) !== 1) {
                throw new ParseFailureException('Unrecognized codespacerange format');
            }

            if (strlen($matchesSpaceRange['start']) !== strlen($matchesSpaceRange['end'])) {
                throw new ParseFailureException(sprintf('Start(%s) and end(%s) of codespacerange don\'t have the same number of bytes', $matchesSpaceRange['start'], $matchesSpaceRange['end']));
            }

            if (($strlen = strlen($matchesSpaceRange['start'])) % 2 !== 0 || !is_int($byteSizeRange = $strlen / 2)) {
                throw new ParseFailureException(sprintf('Codespaceranges must be an even number of hex digits, got %d', $strlen));
            }

            if ($byteSize !== null && $byteSizeRange !== $byteSize) {
                throw new ParseFailureException(sprintf('Byte size of codespaceranges is inconsistent, expected %d, got %d', $byteSize, $byteSizeRange));
            }

            $byteSize = $byteSizeRange;
            $codeSpaceRanges[] = new CodeSpaceRange((int) hexdec($matchesSpaceRange['start']), (int) hexdec($matchesSpaceRange['end']));
        }

        /** @var array<int, list<BFRange|BFChar>> $bfCharRangeInfo where the first index is used to track the position of the element in the CMap */
        $bfCharRangeInfo = [];
        $lastPos = $startOffset;
        while (($beginBFCharPos = $stream->firstPos(ToUnicodeCMapOperator::BeginBFChar, $lastPos, $startOffset + $nrOfBytes)) !== null) {
            $beginBFCharPos += strlen(ToUnicodeCMapOperator::BeginBFChar->value);
            $endBFCharPos = $stream->firstPos(ToUnicodeCMapOperator::EndBFChar, $beginBFCharPos, $startOffset + $nrOfBytes)
                ?? throw new ParseFailureException();
            if (preg_match_all('/\s*<(?P<source>[^>]+)>\s*<(?P<destination>[^>]+)>\s*/', $stream->read($beginBFCharPos, $endBFCharPos - $beginBFCharPos), $matchesBFChar, PREG_SET_ORDER) === 0) {
                throw new ParseFailureException('Unrecognized bfchar format');
            }

            foreach ($matchesBFChar as $matchBFChar) {
                $bfCharRangeInfo[$beginBFCharPos][] = new BFChar((int) hexdec(trim($matchBFChar['source'])), trim($matchBFChar['destination']));
            }
            $lastPos = $endBFCharPos;
        }

        $lastPos = $startOffset;
        while (($beginBFRangePos = $stream->firstPos(ToUnicodeCMapOperator::BeginBFRange, $lastPos, $startOffset + $nrOfBytes)) !== null) {
            $endBFRangePos = $stream->firstPos(ToUnicodeCMapOperator::EndBFRange, $beginBFRangePos, $startOffset + $nrOfBytes)
                ?? throw new ParseFailureException();
            if (preg_match_all('/\s*<(?P<start>[^>]+)>\s*<(?P<end>[^>]+)>\s*(?P<targetString>(<[^>]+>)|(\[\s*(<[^>]+>\s*)+\]))/', $stream->read($beginBFRangePos, $endBFRangePos - $beginBFRangePos), $matchesBFRange, PREG_SET_ORDER) === 0) {
                throw new ParseFailureException('Unrecognized bfrange format');
            }

            foreach ($matchesBFRange as $matchBFRange) {
                $bfCharRangeInfo[$beginBFRangePos][] = new BFRange(
                    (int) hexdec(trim($matchBFRange['start'])),
                    (int) hexdec(trim($matchBFRange['end'])),
                    array_map(
                        fn (string $value) => trim($value),
                        explode('><', rtrim(ltrim(str_replace(' ', '', $matchBFRange['targetString']), '[<'), '>]'))
                    )
                );
            }
            $lastPos = $endBFRangePos;
        }

        ksort($bfCharRangeInfo); // Make sure that Char and Range are in order they occur in the CMap
        return new ToUnicodeCMap(
            $codeSpaceRanges,
            $byteSize !== null ? $byteSize : 2,
            ...array_merge(...$bfCharRangeInfo)
        );
    }
}
