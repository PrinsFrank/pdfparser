<?php
declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\CrossReference\Source\Section\SubSection\Entry;

/** Present in both crossReferenceTable and crossReferenceStream */
class CrossReferenceEntryFreeObject {
    public function __construct(
        public readonly int $objectNumberNextFreeObject,
        public readonly int $generationNumber,
    ) {}
}
