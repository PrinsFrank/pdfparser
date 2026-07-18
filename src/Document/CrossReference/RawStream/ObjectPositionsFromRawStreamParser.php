<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\CrossReference\RawStream;

use PrinsFrank\PdfParser\Stream\Stream;

class ObjectPositionsFromRawStreamParser {
    /** @return array<int, int> */
    public static function parse(Stream $stream): array {
        $inObjNr = $inObjGenerationNumber = $pendingObjMarker = false;
        $startObjNrOffset = $objNrBuffer = $objMarkerBuffer = null;
        $discoveredObjects = [];
        foreach ($stream->chars(0, $stream->getSizeInBytes()) as $byteOffset => $char) {
            if ($char === ' ') {
                if ($inObjNr === true) {
                    $inObjNr = false;
                    $inObjGenerationNumber = true;
                } elseif ($inObjGenerationNumber === true) {
                    $inObjGenerationNumber = false;
                    $pendingObjMarker = true;
                } else {
                    $inObjNr = $inObjGenerationNumber = $pendingObjMarker = false;
                    $startObjNrOffset = $objNrBuffer = $objMarkerBuffer = null;
                }
            } elseif (ctype_digit($char) === true) {
                if ($pendingObjMarker === true) {
                    $pendingObjMarker = false;
                    $objNrBuffer = null;
                } elseif ($inObjGenerationNumber === true) {
                } elseif ($inObjNr === false) {
                    $inObjNr = true;
                    $startObjNrOffset = $byteOffset;
                    $objNrBuffer = $char;
                } elseif ($inObjNr === true) {
                    $objNrBuffer .= $char;
                }
            } elseif ($pendingObjMarker === true) {
                if ($objMarkerBuffer === null && $char === 'o') { // @phpstan-ignore identical.alwaysTrue
                    $objMarkerBuffer = $char;
                } elseif ($objMarkerBuffer === 'o' && $char === 'b') { // @phpstan-ignore identical.alwaysFalse, booleanAnd.alwaysFalse
                    $objMarkerBuffer .= $char;
                } elseif ($objMarkerBuffer === 'ob' && $char === 'j') { // @phpstan-ignore identical.alwaysFalse, booleanAnd.alwaysFalse
                    $discoveredObjects[(int) $objNrBuffer] = $startObjNrOffset;
                    $inObjNr = $inObjGenerationNumber = $pendingObjMarker = false;
                    $startObjNrOffset = $objNrBuffer = $objMarkerBuffer = null;
                } else {
                    $inObjNr = $inObjGenerationNumber = $pendingObjMarker = false;
                    $startObjNrOffset = $objNrBuffer = $objMarkerBuffer = null;
                }
            } else {
                $inObjNr = $inObjGenerationNumber = $pendingObjMarker = false;
                $startObjNrOffset = $objNrBuffer = $objMarkerBuffer = null;
            }
        }

        return $discoveredObjects;
    }
}
