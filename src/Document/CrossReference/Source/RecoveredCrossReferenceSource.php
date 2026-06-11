<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\CrossReference\Source;

use PrinsFrank\PdfParser\Document\CrossReference\Source\Section\CrossReferenceSection;
use PrinsFrank\PdfParser\Document\CrossReference\Source\Section\SubSection\Entry\CrossReferenceEntryCompressed;
use PrinsFrank\PdfParser\Document\CrossReference\Source\Section\SubSection\Entry\CrossReferenceEntryInUseObject;

class RecoveredCrossReferenceSource extends CrossReferenceSource {
    /**
     * @param array<int, int> $recoveredByteOffsetMap where the key is the byte offset and the value the object nr
     * @no-named-arguments
     */
    public function __construct(
        private array $recoveredByteOffsetMap,
        CrossReferenceSection... $crossReferenceSections,
    ) {
        parent::__construct(...$crossReferenceSections);
    }

    public function getCrossReferenceEntry(int $objNumber): CrossReferenceEntryInUseObject|CrossReferenceEntryCompressed|null {
        foreach ($this->recoveredByteOffsetMap as $byteOffset => $recoveredObjNr) {
            if ($recoveredObjNr === $objNumber) {
                return new CrossReferenceEntryInUseObject($byteOffset, 0);
            }
        }

        return parent::getCrossReferenceEntry($objNumber);
    }
}
