<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Security;

readonly class EncryptionContext {
    private string $objectEncryptionKey;

    public function __construct(
        private FileEncryptionKey $fileEncryptionKey,
        private int $objectNumber,
        private int $generationNumber,
    ) {}

    public function getObjectEncryptionKey(): string {
        if (isset($this->objectEncryptionKey)) {
            return $this->objectEncryptionKey;
        }

        return $this->objectEncryptionKey = substr(
            md5(
                $this->fileEncryptionKey->value
                . chr($this->objectNumber & 0xFF)
                . chr(($this->objectNumber >> 8) & 0xFF)
                . chr(($this->objectNumber >> 16) & 0xFF)
                . chr($this->generationNumber & 0xFF)
                . chr(($this->generationNumber >> 8) & 0xFF),
                true,
            ),
            0,
            min(strlen($this->fileEncryptionKey->value) + 5, 16),
        );
    }
}
