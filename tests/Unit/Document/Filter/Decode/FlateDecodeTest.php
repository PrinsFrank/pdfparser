<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Tests\Unit\Document\Filter\Decode;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use PrinsFrank\PdfParser\Document\Filter\Decode\FlateDecode;
use PrinsFrank\PdfParser\Document\Filter\Decode\LZWFlatePredictorValue;
use PrinsFrank\PdfParser\Exception\InvalidArgumentException;
use PrinsFrank\PdfParser\Exception\ParseFailureException;
use PrinsFrank\PdfParser\Exception\RuntimeException;

#[CoversClass(FlateDecode::class)]
class FlateDecodeTest extends TestCase {
    private const TEXT = 'BT /F1 12 Tf (Hello world) Tj ET';

    public function testDecodeBinary(): void {
        $value = str_repeat(self::TEXT, 1000);

        static::assertSame($value, FlateDecode::decodeBinary((string) gzcompress($value), LZWFlatePredictorValue::None));
    }

    public function testDecodeBinaryEmptyStream(): void {
        static::assertSame('', FlateDecode::decodeBinary((string) gzcompress(''), LZWFlatePredictorValue::None));
    }

    public function testDecodeBinaryWithMissingEnd(): void {
        $value = implode(' ', range(1, 20000));
        $compressedValue = (string) gzcompress($value);
        $decodedValue = FlateDecode::decodeBinary(substr($compressedValue, 0, intdiv(strlen($compressedValue), 2)), LZWFlatePredictorValue::None);

        static::assertNotSame('', $decodedValue);
        static::assertStringStartsWith($decodedValue, $value);
    }

    public function testDecodeBinaryThrowsExceptionForInvalidData(): void {
        $this->expectException(ParseFailureException::class);
        $this->expectExceptionMessage('Unable to gzuncompress value');
        FlateDecode::decodeBinary('not a zlib stream', LZWFlatePredictorValue::None);
    }

    public function testDecodeBinaryThrowsExceptionWhenDecodedValueIsTooLarge(): void {
        $this->expectException(ParseFailureException::class);
        $this->expectExceptionMessage('Unable to gzuncompress value as the resulting decoded value was larger than');
        FlateDecode::decodeBinary((string) gzcompress(str_repeat("\0", 64 * 1024 ** 2 + 1)), LZWFlatePredictorValue::None);
    }

    public function testDecodeBinaryWithPNGPredictor(): void {
        static::assertSame(
            '010203' . '010203' . '020304' . '030506' . '040607' . 'ff01',
            bin2hex(FlateDecode::decodeBinary(
                (string) gzcompress((string) hex2bin('00010203' . '01010101' . '02010101' . '03020202' . '04010101' . '01ff02')),
                LZWFlatePredictorValue::PngOptimum,
                3,
            )),
        );
    }

    public function testDecodeBinaryWithPNGPredictorInFirstRow(): void {
        static::assertSame('010203', bin2hex(FlateDecode::decodeBinary((string) gzcompress((string) hex2bin('02010203')), LZWFlatePredictorValue::PngOptimum, 3)));
        static::assertSame('020508', bin2hex(FlateDecode::decodeBinary((string) gzcompress((string) hex2bin('03020406')), LZWFlatePredictorValue::PngOptimum, 3)));
        static::assertSame('010203', bin2hex(FlateDecode::decodeBinary((string) gzcompress((string) hex2bin('04010101')), LZWFlatePredictorValue::PngOptimum, 3)));
    }

    public function testDecodeBinaryThrowsExceptionForUnrecognizedRowAlgorithm(): void {
        $this->expectException(ParseFailureException::class);
        $this->expectExceptionMessage('Unrecognized row algorithm 5');
        FlateDecode::decodeBinary((string) gzcompress((string) hex2bin('05010203')), LZWFlatePredictorValue::PngOptimum, 3);
    }

    public function testDecodeBinaryThrowsExceptionForRowWithoutData(): void {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Expected at least 2 items per row, got 1');
        FlateDecode::decodeBinary((string) gzcompress((string) hex2bin('0001020300')), LZWFlatePredictorValue::PngOptimum, 3);
    }

    public function testDecodeBinaryThrowsExceptionForInvalidNumberOfColumns(): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Nr of columns should be equal to or bigger than 1, 0 given');
        FlateDecode::decodeBinary((string) gzcompress(self::TEXT), LZWFlatePredictorValue::None, 0);
    }

    public function testDecodeBinaryThrowsExceptionForTIFFPredictor(): void {
        $this->expectException(ParseFailureException::class);
        $this->expectExceptionMessage('Unsupported predictor 2');
        FlateDecode::decodeBinary((string) gzcompress(self::TEXT), LZWFlatePredictorValue::TIFFPredictor2);
    }
}
