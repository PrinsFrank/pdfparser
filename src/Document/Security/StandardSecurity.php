<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Security;

use PrinsFrank\PdfParser\Document\Encryption\RC4;
use PrinsFrank\PdfParser\Document\Object\Decorator\EncryptDictionary;
use PrinsFrank\PdfParser\Exception\NotImplementedException;
use PrinsFrank\PdfParser\Exception\ParseFailureException;
use SensitiveParameter;

class StandardSecurity {
    /** @see 7.6.4.3.2 a */
    public const PADDING_STRING = "\x28\xBF\x4E\x5E\x4E\x75\x8A\x41\x64\x00\x4E\x56\xFF\xFA\x01\x08\x2E\x2E\x00\xB6\xD0\x68\x3E\x80\x2F\x0C\xA9\xFE\x64\x53\x69\x7A";
    private const PASSWORD_LENGTH = 32;

    public function __construct(
        #[SensitiveParameter] private ?string $userPassword = null,
        #[SensitiveParameter] private readonly ?string $ownerPassword = null,
    ) {
    }

    /** @see 7.6.4.4.3, 7.6.4.4.4 and 7.6.4.4.5 */
    public function isUserPasswordValid(EncryptDictionary $encryptDictionary, string $firstID): bool {
        $userPasswordEntry = $encryptDictionary->getUserPasswordEntry();
        $securityHandlerRevision = $encryptDictionary->getStandardSecurityHandlerRevision();

        $fileEncryptionKey = $this->getUserFileEncryptionKey($encryptDictionary, $firstID);
        if ($securityHandlerRevision === StandardSecurityHandlerRevision::v2) { // @see 7.6.4.4.3, step b
            return hash_equals($userPasswordEntry, RC4::encrypt($fileEncryptionKey, self::PADDING_STRING));
        }

        if (in_array($securityHandlerRevision, [StandardSecurityHandlerRevision::v3, StandardSecurityHandlerRevision::v4], true)) { // @see 7.6.4.4.4, step b through e
            $hash = md5(self::PADDING_STRING . $firstID, true);
            $encryptedHash = RC4::encrypt($fileEncryptionKey, $hash);
            for ($i = 1; $i <= 19; $i++) {
                $encryptedHash = RC4::encrypt(
                    implode('', array_map(
                        fn($c) => chr(ord($c) ^ $i),
                        str_split($fileEncryptionKey)
                    )),
                    $encryptedHash,
                );
            }

            return hash_equals(substr($userPasswordEntry, 0, 16), $encryptedHash);
        }

        throw new NotImplementedException('Unsupported security handler revision: ' . $securityHandlerRevision->value);
    }

    /** @see 7.6.4.4.6 */
    public function isOwnerPasswordValid(EncryptDictionary $encryptDictionary, string $firstID): bool {
        $fileEncryptionKey = $this->getOwnerFileEncryptionKey($encryptDictionary, $firstID);

        $ownerPasswordEntry = $encryptDictionary->getOwnerPasswordEntry();
        if ($encryptDictionary->getStandardSecurityHandlerRevision() === StandardSecurityHandlerRevision::v2) {
            $userPassword = RC4::encrypt($fileEncryptionKey, $ownerPasswordEntry);
        } else {
            $userPassword = $ownerPasswordEntry;
            for ($i = 19; $i >= 0; $i--) {
                $userPassword = RC4::encrypt(
                    implode('', array_map(
                        fn($c) => chr(ord($c) ^ $i),
                        str_split($fileEncryptionKey)
                    )),
                    $userPassword,
                );
            }
        }

        if ($this->userPassword !== null && $userPassword !== $this->userPassword) {
            return false;
        }

        $this->userPassword = $userPassword;
        return $this->isUserPasswordValid($encryptDictionary, $firstID);
    }

    /** @see 7.6.4.4.2 */
    public function getUserFileEncryptionKey(EncryptDictionary $encryptDictionary, string $firstIDValue): string {
        if (in_array($encryptDictionary->getStandardSecurityHandlerRevision(), [StandardSecurityHandlerRevision::v2, StandardSecurityHandlerRevision::v3, StandardSecurityHandlerRevision::v4], true) === false) {
            throw new NotImplementedException('Unsupported security handler revision: ' . $encryptDictionary->getStandardSecurityHandlerRevision()->value);
        }

        $fileEncryptionKeyLengthInBits = $encryptDictionary->getLengthFileEncryptionKeyInBits() ?? throw new ParseFailureException();
        if ($encryptDictionary->getSecurityAlgorithm() === SecurityAlgorithm::AES_Key_length_256) { // V = 4
            throw new NotImplementedException('AES-based stream decryption is not yet supported.');
        }

        if ($fileEncryptionKeyLengthInBits % 8 !== 0 || !is_int($fileEncryptionKeyLengthInBytes = $fileEncryptionKeyLengthInBits / 8)) {
            throw new ParseFailureException('Unsupported file encryption key length in bits: ' . $fileEncryptionKeyLengthInBits);
        }

        $hashedString =
            $this->getPaddedUserPassword() // step a+b
            . $encryptDictionary->getOwnerPasswordEntry() // step c
            . pack('V', $encryptDictionary->getPValue()) // step d
            . $firstIDValue; // step e
        if ($encryptDictionary->getStandardSecurityHandlerRevision()->value >= 4 && $encryptDictionary->isMetadataEncrypted() === false) {
            $hashedString .= "\xFF\xFF\xFF\xFF";
        }

        $md5Hash = md5($hashedString, true);
        if ($encryptDictionary->getStandardSecurityHandlerRevision() === StandardSecurityHandlerRevision::v2) {
            return substr($md5Hash, 0, 5);
        }

        for ($i = 1; $i <= 50; $i++) { // step h
            $md5Hash = md5(substr($md5Hash, 0, $fileEncryptionKeyLengthInBytes), true);
        }

        return substr($md5Hash, 0, $fileEncryptionKeyLengthInBytes);
    }

    private function getOwnerFileEncryptionKey(EncryptDictionary $encryptDictionary, string $firstIDValue) {
        if (in_array($encryptDictionary->getStandardSecurityHandlerRevision(), [StandardSecurityHandlerRevision::v2, StandardSecurityHandlerRevision::v3, StandardSecurityHandlerRevision::v4], true) === false) {
            throw new NotImplementedException('Unsupported security handler revision: ' . $encryptDictionary->getStandardSecurityHandlerRevision()->value);
        }

        $fileEncryptionKeyLengthInBits = $encryptDictionary->getLengthFileEncryptionKeyInBits() ?? throw new ParseFailureException();
        if ($encryptDictionary->getSecurityAlgorithm() === SecurityAlgorithm::AES_Key_length_256) { // V = 4
            throw new NotImplementedException('AES-based stream decryption is not yet supported.');
        }

        if ($fileEncryptionKeyLengthInBits % 8 !== 0 || !is_int($fileEncryptionKeyLengthInBytes = $fileEncryptionKeyLengthInBits / 8)) {
            throw new ParseFailureException('Unsupported file encryption key length in bits: ' . $fileEncryptionKeyLengthInBits);
        }

        $md5Hash = md5($this->getPaddedOwnerPassword(), true);
        if ($encryptDictionary->getStandardSecurityHandlerRevision() !== StandardSecurityHandlerRevision::v2) {
            for ($i = 1; $i <= 50; $i++) { // step c
                $md5Hash = md5($md5Hash, true);
            }
        }

        if ($encryptDictionary->getStandardSecurityHandlerRevision() === StandardSecurityHandlerRevision::v2) {
            return substr($md5Hash, 0, 5);
        }

        return substr($md5Hash, 0, $fileEncryptionKeyLengthInBytes);
    }

    /** @see 7.6.4.3.2 step a */
    public function getPaddedUserPassword(): string {
        return substr($this->userPassword ?? '', 0, self::PASSWORD_LENGTH)
            . substr(self::PADDING_STRING, 0, max(0, self::PASSWORD_LENGTH - strlen($this->userPassword ?? '')));
    }

    /** @see 7.6.4.3.2 step a */
    public function getPaddedOwnerPassword(): string {
        return substr($this->ownerPassword ?? $this->userPassword ?? '', 0, self::PASSWORD_LENGTH)
            . substr(self::PADDING_STRING, 0, max(0, self::PASSWORD_LENGTH - strlen($this->ownerPassword ?? $this->userPassword ?? '')));
    }
}
