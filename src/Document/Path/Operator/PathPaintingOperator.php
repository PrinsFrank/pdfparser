<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Path\Operator;

/** @internal */
enum PathPaintingOperator: string {
    case STROKE = 'S';
    case CLOSE_STROKE = 's';
    case FILL = 'f';

    /** @deprecated, identical to self::FILL */
    case FILL_DEPRECATED = 'F';
    case FILL_EVEN_ODD = 'f*';
    case FILL_STROKE = 'B';
    case FILL_STROKE_EVEN_ODD = 'B*';
    case CLOSE_FILL_STROKE = 'b*';
    case END = 'n';
}
