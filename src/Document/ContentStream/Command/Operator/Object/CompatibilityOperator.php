<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\ContentStream\Command\Operator\Object;

/**
 * @internal
 *
 * @specification Table 33 - Compatibility operators
 */
enum CompatibilityOperator: string {
    case BeginCompatibilitySection = 'BX';
    case EndCompatibilitySection = 'EX';
}
