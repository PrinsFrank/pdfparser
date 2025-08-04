<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Filter\Decode;

/** @internal */
enum PNGPredictorAlgorithm: int {
    case None = 0;
    case Sub = 1;
    case Up = 2;
    case Average = 3;
    case Paeth = 4;
}
