<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Security;

class StandardSecurity implements Security {
    /** @see 7.6.4.3.2 a */
    public const PADDING_STRING = "\x28\xBF\x4E\x5E\x4E\x75\x8A\x41\x64\x00\x4E\x56\xFF\xFA\x01\x08\x2E\x2E\x00\xB6\xD0\x68\x3E\x80\x2F\x0C\xA9\xFE\x64\x53\x69\x7A";
    private const PASSWORD_LENGTH = 32;

    public function __construct(
        private readonly ?string $userPassword,
        private readonly ?string $ownerPassword,
    ) {
    }

    public function getFileEncryptionKey(string $oValue, int $pValue, string $firstIDValue, StandardSecurityHandlerRevision $securityHandlerRevision, ?int $length): string {
        $finalMD5Hash = md5($this->getPaddedUserPassword() . $oValue . pack('V', $pValue) . $firstIDValue, true);
        if ($securityHandlerRevision === StandardSecurityHandlerRevision::v2) {
            $fileEncryptionKeyLength = 5;
        } else {
            $fileEncryptionKeyLength = ($length ?? 128) / 8;
        }

        return substr($finalMD5Hash, 0, $fileEncryptionKeyLength);
    }

    public function getPaddedUserPassword(): string {
        return substr($this->userPassword ?? '', 0, self::PASSWORD_LENGTH)
            . substr(self::PADDING_STRING, 0, max(0, self::PASSWORD_LENGTH - strlen($this->userPassword ?? '')));
    }
}
