<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\CrossReference\Source\Section;

use PrinsFrank\PdfParser\Document\CrossReference\Source\Section\SubSection\CrossReferenceSubSection;
use PrinsFrank\PdfParser\Document\CrossReference\Source\Section\SubSection\Entry\CrossReferenceEntryCompressed;
use PrinsFrank\PdfParser\Document\CrossReference\Source\Section\SubSection\Entry\CrossReferenceEntryInUseObject;
use PrinsFrank\PdfParser\Document\Dictionary\Dictionary;

/** There are multiple crossReference sections if there are incremental updates. See 7.5.6 */
readonly class CrossReferenceSection {
    /** @var list<CrossReferenceSubSection> */
    public array $crossReferenceSubSections;

    /** @no-named-arguments */
    public function __construct(
        public Dictionary $dictionary,
        CrossReferenceSubSection... $crossReferenceSubSections,
    ) {
        $this->crossReferenceSubSections = $crossReferenceSubSections;
    }

    public function getCrossReferenceEntry(int $objNumber): CrossReferenceEntryInUseObject|CrossReferenceEntryCompressed|null {
        foreach ($this->crossReferenceSubSections as $crossReferenceSubSection) {
            if ($crossReferenceSubSection->containsObject($objNumber)) {
                return $crossReferenceSubSection->getCrossReferenceEntry($objNumber);
            }
        }

        return null;
    }
}
