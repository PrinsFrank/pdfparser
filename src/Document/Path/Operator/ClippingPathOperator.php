<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Path\Operator;

/** @internal */
enum ClippingPathOperator: string {
    case INTERSECT = 'W';
    case INTERSECT_EVEN_ODD = 'W*';
}
