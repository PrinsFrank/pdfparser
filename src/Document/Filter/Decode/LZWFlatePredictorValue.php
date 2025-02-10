<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Filter\Decode;

/** @internal */
enum LZWFlatePredictorValue: int {
    case None = 1;
    case TIFFPredictor2 = 2;
    case PngNone = 10;
    case PngSub = 11;
    case PngUp = 12;
    case PngAverage = 13;
    case PngPaeth = 14;
    case PngOptimum = 15;
}
