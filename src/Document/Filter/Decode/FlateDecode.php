<?php
declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Filter\Decode;

use PrinsFrank\PdfParser\Exception\InvalidArgumentException;
use PrinsFrank\PdfParser\Exception\ParseFailureException;
use PrinsFrank\PdfParser\Exception\PdfParserException;
use PrinsFrank\PdfParser\Exception\RuntimeException;

/** @internal */
class FlateDecode {
    /**
     * @throws PdfParserException
     * @return string in binary format
     */
    public static function decodeBinary(string $value, LZWFlatePredictorValue $predictor, int $columns = 1): string {
        if ($columns < 1) {
            throw new InvalidArgumentException(sprintf('Nr of columns should be equal to or bigger than 1, %d given', $columns));
        }

        $decodedValue = @gzuncompress($value);
        if ($decodedValue === false) {
            if (($tmpFile = tempnam(sys_get_temp_dir(), 'gz')) === false) {
                throw new RuntimeException('Unable to create temporary file');
            }

            file_put_contents($tmpFile, "\x1f\x8b\x08\x00\x00\x00\x00\x00" . $value);
            $decodedValue = file_get_contents('compress.zlib://' . $tmpFile);
            unlink($tmpFile);
            if (in_array($decodedValue, ['', false], true)) {
                throw new ParseFailureException('Unable to gzuncompress value "' . substr(trim($value), 0, 30) . '..."');
            }
        }

        if ($predictor === LZWFlatePredictorValue::None) {
            return $decodedValue;
        }

        if ($predictor === LZWFlatePredictorValue::TIFFPredictor2) {
            throw new ParseFailureException('Unsupported predictor ' . $predictor->value);
        }

        $hexTable = array_map(fn(string $row) => str_split($row, 2), str_split(bin2hex($decodedValue), ($columns + 1) * 2));
        $decodedValue = '';
        foreach ($hexTable as $rowIndex => $row) {
            if (!is_array($row) || !array_is_list($row) || count($row) < 2) {
                throw new RuntimeException(sprintf('Expected at least 2 items per row, got %d', count($row)));
            }

            if (!is_int($algorithmNumber = hexdec($row[0]))) {
                throw new ParseFailureException(sprintf('Expected algorithm number to be an integer, got %s', $algorithmNumber));
            }

            $rowAlgorithm = PNGPredictorAlgorithm::tryFrom($algorithmNumber)
                ?? throw new ParseFailureException(sprintf('Unrecognized row algorithm %d', $algorithmNumber));
            if ($rowAlgorithm === PNGPredictorAlgorithm::None) {
                $decodedValue .= implode('', array_slice($row, 1));
            } elseif ($rowAlgorithm === PNGPredictorAlgorithm::Sub) {
                foreach ($row as $columnIndex => $columnValue) {
                    if ($columnIndex === 0) {
                        continue;
                    }

                    $left = $columnIndex > 1 ? hexdec($hexTable[$rowIndex][$columnIndex - 1]) : 0;
                    $hexTable[$rowIndex][$columnIndex] = str_pad(dechex((hexdec($columnValue) + $left) % 256), 2, '0', STR_PAD_LEFT);
                }

                $decodedValue .= implode('', array_slice($hexTable[$rowIndex], 1));
            } elseif ($rowAlgorithm === PNGPredictorAlgorithm::Up) {
                if ($rowIndex === 0) {
                    $decodedValue .= implode('', array_slice($row, 1));

                    continue;
                }

                foreach ($row as $columnIndex => $columnValue) {
                    /** @phpstan-ignore offsetAccess.notFound, offsetAccess.notFound */
                    $hexTable[$rowIndex][$columnIndex] = str_pad(dechex((hexdec($columnValue) + hexdec($hexTable[$rowIndex - 1][$columnIndex])) % 256), 2, '0', STR_PAD_LEFT);
                }

                $decodedValue .= implode('', array_slice($hexTable[$rowIndex], 1));
            } elseif ($rowAlgorithm === PNGPredictorAlgorithm::Average) {
                foreach ($row as $columnIndex => $columnValue) {
                    if ($columnIndex === 0) {
                        continue;
                    }

                    $left = $columnIndex > 1 ? hexdec($hexTable[$rowIndex][$columnIndex - 1]) : 0;
                    $above = $rowIndex > 0 ? hexdec($hexTable[$rowIndex - 1][$columnIndex]) : 0;
                    $hexTable[$rowIndex][$columnIndex] = str_pad(dechex((hexdec($columnValue) + (int) floor(($left + $above) / 2)) % 256), 2, '0', STR_PAD_LEFT);
                }

                $decodedValue .= implode('', array_slice($hexTable[$rowIndex], 1));
            } elseif ($rowAlgorithm === PNGPredictorAlgorithm::Paeth) {
                foreach ($row as $columnIndex => $columnValue) {
                    if ($columnIndex === 0) {
                        continue;
                    }

                    $left = $columnIndex > 1 ? hexdec($hexTable[$rowIndex][$columnIndex - 1]) : 0;
                    $above = $rowIndex > 0 ? hexdec($hexTable[$rowIndex - 1][$columnIndex]) : 0;
                    $upperLeft = ($rowIndex > 0 && $columnIndex > 1) ? hexdec($hexTable[$rowIndex - 1][$columnIndex - 1]) : 0;

                    $p = $left + $above - $upperLeft;
                    $pa = abs($p - $left);
                    $pb = abs($p - $above);
                    $pc = abs($p - $upperLeft);

                    if ($pa <= $pb && $pa <= $pc) {
                        $predictor = $left;
                    } elseif ($pb <= $pc) {
                        $predictor = $above;
                    } else {
                        $predictor = $upperLeft;
                    }

                    $hexTable[$rowIndex][$columnIndex] = str_pad(dechex((hexdec($columnValue) + $predictor) % 256), 2, '0', STR_PAD_LEFT);
                }

                $decodedValue .= implode('', array_slice($hexTable[$rowIndex], 1));
            }
        }

        if (($decodedValue = hex2bin($decodedValue)) === false) {
            throw new ParseFailureException('Unable to hex2bin value "' . substr(trim($value), 0, 30) . '..."');
        }

        return $decodedValue;
    }
}
