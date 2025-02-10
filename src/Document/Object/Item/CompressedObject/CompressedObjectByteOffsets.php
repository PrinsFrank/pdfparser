<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Object\Item\CompressedObject;

/** @internal */
class CompressedObjectByteOffsets {
    /** @param array<int, int> $objectNumberByteOffsets */
    public function __construct(
        private readonly array $objectNumberByteOffsets,
    ) {
    }

    public function getRelativeByteOffsetForObject(int $objNumber): ?int {
        return $this->objectNumberByteOffsets[$objNumber] ?? null;
    }

    public function getNextRelativeByteOffset(int $currentByteOffset): ?int {
        $byteOffsets = array_values($this->objectNumberByteOffsets);
        sort($byteOffsets);
        foreach ($byteOffsets as $byteOffset) {
            if ($byteOffset > $currentByteOffset) {
                return $byteOffset;
            }
        }

        return null;
    }
}
