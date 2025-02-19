<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Path\Operator;

/** @internal */
enum PathConstructionOperator: string {
    case MOVE = 'm';
    case LINE = 'l';
    case CURVE_BEZIER_123 = 'c';
    case CURVE_BEZIER_23 = 'v';
    case CURVE_BEZIER_13 = 'y';
    case CLOSE = 'h';
    case RECTANGLE = 're';
}
