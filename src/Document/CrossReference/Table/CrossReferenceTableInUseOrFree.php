<?php
declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\CrossReference\Table;

/** @internal */
enum CrossReferenceTableInUseOrFree: string {
    case IN_USE = 'n';
    case FREE = 'f';
}
