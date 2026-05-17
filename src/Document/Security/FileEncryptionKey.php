<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Security;

use SensitiveParameter;

readonly class FileEncryptionKey {
    public function __construct(
        #[SensitiveParameter]
        public string $value,
    ) {}

    public function toHex(): string {
        return bin2hex($this->value);
    }
}
