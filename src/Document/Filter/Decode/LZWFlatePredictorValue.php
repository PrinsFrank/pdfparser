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

    /**
     * The postprediction data for each PNG-predicted row shall begin with an explicit algorithm tag;
     * therefore, different rows can be predicted with different algorithms to improve compression.
     * TIFF Predictor 2 has no such identifier; the same algorithm applies to all rows.
     */
    public function hasRowAlgorithm(): bool {
        return $this !== self::None
            && $this !== self::TIFFPredictor2;
    }
}
