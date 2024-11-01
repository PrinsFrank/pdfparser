<?php
declare(strict_types=1);

namespace PrinsFrank\PdfParser\Unused\Object\ObjectItem;

use PrinsFrank\PdfParser\Document\Dictionary\DictionaryParser;

class ObjectItemParser {
    public static function parse(?string $decodedStream): ObjectItemCollection {
        if ($decodedStream === null) {
            return new ObjectItemCollection();
        }

        $firstEOL = strcspn($decodedStream, "\r\n");
        $objectIndicesLine = substr($decodedStream, 0, $firstEOL);
        $items = explode(' ', $objectIndicesLine);
        $objectLocations = [];
        foreach ($items as $key => $value) {
            if ($key % 2 === 1) {
                $objectLocations[$items[$key - 1]] = (int) $value;
            }
        }

        $objectStreamContent = substr($decodedStream, $firstEOL);
        $objectLocationIndices = array_values($objectLocations);
        sort($objectLocationIndices);
        $objectItems = [];
        foreach ($objectLocationIndices as $index => $objectOffset) {
            if (array_key_exists($index + 1, $objectLocationIndices)) {
                $objectContent = substr($objectStreamContent, $objectOffset, ($objectLocationIndices[$index + 1] ?? 0) - $objectOffset);
            } else {
                $objectContent = substr($objectStreamContent, $objectOffset);
            }
            $objectId = array_search($objectOffset, $objectLocations, true);
            $objectItems[] = new ObjectItem(
                (int) $objectId,
                $objectContent,
                DictionaryParser::parse($objectContent)
            );
        }

        return new ObjectItemCollection(... $objectItems);
    }
}
