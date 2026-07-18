<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\CrossReference\RawStream;

use PrinsFrank\PdfParser\Exception\ParseFailureException;
use PrinsFrank\PdfParser\Stream\Stream;

class ObjectPositionsFromRawStreamParser {
    private const CHUNK_SIZE = 1024 * 1024;
    private const CHUNK_OVERLAP = 20;

    /** @return array<int, int> */
    public static function parse(Stream $stream): array {
        $nrOfChunks = ceil($stream->getSizeInBytes() / self::CHUNK_SIZE);

        $discoveredObjects = [];
        for ($chunkIndex = 0; $chunkIndex < $nrOfChunks; $chunkIndex++) {
            $chunkContent = $stream->read(
                $chunkStart = max(0, ($chunkIndex * self::CHUNK_SIZE) - self::CHUNK_OVERLAP),
                self::CHUNK_OVERLAP + self::CHUNK_SIZE,
            );

            if (preg_match_all('/(\d+)\s+\d+\s+obj/', $chunkContent, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER) === false) {
                throw new ParseFailureException();
            }

            foreach ($matches as $match) {
                $discoveredObjects[(int) $match[1][0]] = $match[1][1] + $chunkStart;
            }
        }

        return $discoveredObjects;
    }
}
