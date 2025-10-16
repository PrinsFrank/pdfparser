<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Filter\Decode;

use PrinsFrank\PdfParser\Exception\ParseFailureException;
use PrinsFrank\PdfParser\Stream\FileStream;
use PrinsFrank\PdfParser\Stream\Stream;

/**
 * See Section 7.4.6
 */
class CCITTFaxDecode {
    private const BYTE_ORDER_LITTLE_ENDIAN = 'II';
    private const MAGIC_NUMBER_TIFF = 42;
    private const IFD_OFFSET_IN_BYTES = 8;
    private const END_OF_IFD_OFFSET = 0;

    public static function addHeaderAndIFD(Stream $stream, int $columns, int $rows, int $k): Stream {
        $rawData = $stream->toString();
        $ifdEntries = [
            self::createIfdEntry(TiffTag::ImageWidth, 3, 1, $columns),
            self::createIfdEntry(TiffTag::ImageHeight, 3, 1, $rows),
            self::createIfdEntry(TiffTag::Compression, 3, 1, $k >= 0 ? 3 : 4),
            self::createIfdEntry(TiffTag::PhotometricInterpretation, 3, 1, 0),
            self::createIfdEntry(TiffTag::RowsPerStrip, 3, 1, $rows),
            self::createIfdEntry(TiffTag::StripByteCounts, 4, 1, strlen($rawData)),
        ];

        $ifdEntries[] = self::createIfdEntry(TiffTag::StripOffsets, 4, 1, 8 + 2 + (12 * (count($ifdEntries) + 1)) + 4);

        return FileStream::fromString(
            self::BYTE_ORDER_LITTLE_ENDIAN
                . pack("v", self::MAGIC_NUMBER_TIFF)
                . pack("V", self::IFD_OFFSET_IN_BYTES)
                . pack("v", count($ifdEntries))
                . implode('', $ifdEntries)
                . pack("V", self::END_OF_IFD_OFFSET)
                . $rawData
        );
    }

    /**
     * @param int<3,4> $type
     * @param int<1, max> $count
     */
    private static function createIfdEntry(TiffTag $tiffTag, int $type, int $count, int $value): string {
        $entry = pack("v", $tiffTag->value) . pack("v", $type) . pack("V", $count);

        if ($type === 3 && $count === 1) {
            return $entry . pack("v", $value) . "\x00\x00";
        } elseif ($type === 4 || ($type === 3 && $count > 1)) {
            return $entry . pack("V", $value);
        } else {
            throw new ParseFailureException("Unsupported IFD entry type or count.");
        }
    }
}
