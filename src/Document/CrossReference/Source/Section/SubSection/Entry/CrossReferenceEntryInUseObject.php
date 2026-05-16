<?php
declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\CrossReference\Source\Section\SubSection\Entry;

/** Present in both crossReferenceTable and crossReferenceStream */
readonly class CrossReferenceEntryInUseObject {
    public function __construct(
        public int $byteOffsetInDecodedStream,
        public int $generationNumber,
    ) {}
}
