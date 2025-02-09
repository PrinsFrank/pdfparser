<?php
declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\CrossReference\Source\Section\SubSection;

use PrinsFrank\PdfParser\Document\CrossReference\Source\Section\SubSection\Entry\CrossReferenceEntryCompressed;
use PrinsFrank\PdfParser\Document\CrossReference\Source\Section\SubSection\Entry\CrossReferenceEntryFreeObject;
use PrinsFrank\PdfParser\Document\CrossReference\Source\Section\SubSection\Entry\CrossReferenceEntryInUseObject;
use PrinsFrank\PdfParser\Exception\InvalidArgumentException;
use PrinsFrank\PdfParser\Exception\RuntimeException;

class CrossReferenceSubSection {
    /** @var array<CrossReferenceEntryInUseObject|CrossReferenceEntryFreeObject|CrossReferenceEntryCompressed> */
    public array $crossReferenceEntries = [];

    /**
     * @phpstan-assert int<0, max> $nrOfEntries
     *
     * @throws InvalidArgumentException
     *
     * @no-named-arguments
     */
    public function __construct(
        public readonly int $firstObjectNumber,
        public readonly int $nrOfEntries,
        CrossReferenceEntryInUseObject|CrossReferenceEntryFreeObject|CrossReferenceEntryCompressed... $crossReferenceEntries
    ) {
        if ($this->nrOfEntries < 0) {
            throw new InvalidArgumentException('$nrOfEntries should be a positive number');
        }

        $this->crossReferenceEntries = $crossReferenceEntries;
    }

    public function containsObject(int $objNumber): bool {
        return $objNumber >= $this->firstObjectNumber
            && $objNumber < $this->firstObjectNumber + $this->nrOfEntries;
    }

    /** @throws RuntimeException */
    public function getCrossReferenceEntry(int $objNumber): CrossReferenceEntryInUseObject|CrossReferenceEntryCompressed|null {
        if (self::containsObject($objNumber) === false) {
            return null;
        }

        $object = $this->crossReferenceEntries[$objNumber - $this->firstObjectNumber]
            ?? throw new RuntimeException(sprintf('Object with key %d should exist when self::containsObject is true', $objNumber - $this->firstObjectNumber));
        if ($object instanceof CrossReferenceEntryFreeObject) {
            throw new RuntimeException('Cross reference entry for object should point to either a compressed or uncompressed entry, not a free object nr');
        }

        return $object;
    }
}
