<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\ContentStream\Command\Operator\Object;

/**
 * @internal
 *
 * @specification table 90 - Inline image operators
 */
enum InlineImageOperator: string {
    case Begin = 'BI';
    case BeginImageData = 'ID';
    case End = 'EI';
}
