<?php
declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Filter\Decode;

use PrinsFrank\PdfParser\Exception\InvalidArgumentException;
use PrinsFrank\PdfParser\Exception\ParseFailureException;
use PrinsFrank\PdfParser\Exception\PdfParserException;
use PrinsFrank\PdfParser\Exception\RuntimeException;

/** @internal */
class FlateDecode {
    private const BYTES_IN_MEGABYTE = 1024 ** 2;

    /**
     * @throws PdfParserException
     * @return string in binary format
     */
    public static function decodeBinary(string $value, LZWFlatePredictorValue $predictor, int $columns = 1): string {
        if ($columns < 1) {
            throw new InvalidArgumentException(sprintf('Nr of columns should be equal to or bigger than 1, %d given', $columns));
        }

        $decodedValue = self::decode($value, 64 * self::BYTES_IN_MEGABYTE);
        if ($predictor === LZWFlatePredictorValue::None) {
            return $decodedValue;
        }

        if ($predictor === LZWFlatePredictorValue::TIFFPredictor2) {
            throw new ParseFailureException('Unsupported predictor ' . $predictor->value);
        }

        $predictedValue = '';
        $previousRow = str_repeat("\0", $columns);
        for ($offset = 0, $length = strlen($decodedValue); $offset < $length; $offset += $columns + 1) {
            if (($row = substr($decodedValue, $offset + 1, $columns)) === '') {
                throw new RuntimeException('Expected at least 2 items per row, got 1');
            }

            $rowAlgorithm = PNGPredictorAlgorithm::tryFrom($algorithmNumber = ord($decodedValue[$offset]))
                ?? throw new ParseFailureException(sprintf('Unrecognized row algorithm %d', $algorithmNumber));
            if ($rowAlgorithm !== PNGPredictorAlgorithm::None) {
                for ($columnIndex = 0, $rowLength = strlen($row); $columnIndex < $rowLength; $columnIndex++) {
                    $left = $columnIndex > 0 ? ord($row[$columnIndex - 1]) : 0;
                    $above = ord($previousRow[$columnIndex]);
                    $upperLeft = $columnIndex > 0 ? ord($previousRow[$columnIndex - 1]) : 0;
                    $row[$columnIndex] = chr((ord($row[$columnIndex]) + match ($rowAlgorithm) {
                        PNGPredictorAlgorithm::Sub => $left,
                        PNGPredictorAlgorithm::Up => $above,
                        PNGPredictorAlgorithm::Average => intdiv($left + $above, 2),
                        PNGPredictorAlgorithm::Paeth => self::paethPredictor($left, $above, $upperLeft),
                    }) % 256);
                }
            }

            $predictedValue .= $row;
            $previousRow = $row;
        }

        return $predictedValue;
    }

    private static function paethPredictor(int $left, int $above, int $upperLeft): int {
        $p = $left + $above - $upperLeft;
        $pa = abs($p - $left);
        $pb = abs($p - $above);
        $pc = abs($p - $upperLeft);
        if ($pa <= $pb && $pa <= $pc) {
            return $left;
        }

        return $pb <= $pc ? $above : $upperLeft;
    }

    /** @param int<0, max> $maxBytes */
    protected static function decode(string $value, int $maxBytes): string {
        $decodedValue = @gzuncompress($value, $maxBytes);
        if ($decodedValue === false) {
            if (($tmpFile = tempnam(sys_get_temp_dir(), 'gz')) === false) {
                throw new RuntimeException('Unable to create temporary file');
            }

            try {
                if (file_put_contents($tmpFile, "\x1f\x8b\x08\x00\x00\x00\x00\x00" . $value) === false) {
                    throw new RuntimeException('Unable to write data to be uncompressed to temporary file');
                }

                $decodedValue = file_get_contents('compress.zlib://' . $tmpFile, length: $maxBytes + 1);
                if (in_array($decodedValue, ['', false], true)) {
                    throw new ParseFailureException('Unable to gzuncompress value "' . substr(trim($value), 0, 30) . '..."');
                }
            } finally {
                is_file($tmpFile) && unlink($tmpFile);
            }
        }

        if (strlen($decodedValue) > $maxBytes) {
            throw new ParseFailureException('Unable to gzuncompress value as the resulting decoded value was larger than a gigabyte');
        }

        return $decodedValue;
    }
}
