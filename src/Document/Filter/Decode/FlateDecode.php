<?php
declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Filter\Decode;

use PrinsFrank\PdfParser\Exception\InvalidArgumentException;
use PrinsFrank\PdfParser\Exception\ParseFailureException;
use PrinsFrank\PdfParser\Exception\PdfParserException;
use PrinsFrank\PdfParser\Exception\RuntimeException;

/** @internal */
class FlateDecode {
    /** @throws PdfParserException */
    public static function decode(string $value, LZWFlatePredictorValue $predictor, int $columns = 1): string {
        if ($columns < 1) {
            throw new InvalidArgumentException(sprintf('Nr of columns should be equal to or bigger than 1, %d given', $columns));
        }

        $decodedValue = @gzuncompress($value);
        if ($decodedValue === false) {
            throw new ParseFailureException('Unable to gzuncompress value "' . substr(trim($value), 0, 30) . '..."');
        }

        $decodedValue = bin2hex($decodedValue);
        if ($predictor !== LZWFlatePredictorValue::None) {
            $hexTable = array_map(fn (string $row) => str_split($row, 2), str_split($decodedValue, ($columns + 1) * 2));
            $decodedValue = '';
            foreach ($hexTable as $rowIndex => $row) {
                if (!is_array($row) || !array_is_list($row) || count($row) < 2) {
                    throw new RuntimeException(sprintf('Expected at least 2 items per row, got %d', count($row)));
                }

                $rowAlgorithm = PNGFilterAlgorithm::tryFrom((int) $row[0]);
                if ($rowAlgorithm === null) {
                    throw new ParseFailureException(sprintf('Unrecognized row algorithm %d', (int) $row[0]));
                }

                if ($rowAlgorithm !== PNGFilterAlgorithm::Up) {
                    throw new ParseFailureException(sprintf('PNG filters other than "Up" are currently not supported, "%s" given', $rowAlgorithm->name));
                }

                if ($rowIndex === 0) {
                    $decodedValue .= implode('', array_slice($row, 1));

                    continue; // We can't do an up transform with the top row
                }

                foreach ($row as $columnIndex => $columnValue) {
                    /** @phpstan-ignore offsetAccess.notFound, offsetAccess.notFound */
                    $hexTable[$rowIndex][$columnIndex] = str_pad(dechex((hexdec($columnValue) + hexdec($hexTable[$rowIndex - 1][$columnIndex])) % 256), 2, '0', STR_PAD_LEFT);
                }

                $decodedValue .= implode('', array_slice($hexTable[$rowIndex], 1));
            }
        }

        return $decodedValue;
    }
}
