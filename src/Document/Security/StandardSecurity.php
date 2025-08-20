<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Security;

use _PHPStan_ce257d9ac\Nette\NotSupportedException;
use PrinsFrank\PdfParser\Document\Encryption\RC4;
use PrinsFrank\PdfParser\Document\Object\Decorator\EncryptDictionary;
use PrinsFrank\PdfParser\Exception\ParseFailureException;
use SensitiveParameter;

class StandardSecurity implements Security {
    /** @see 7.6.4.3.2 a */
    public const PADDING_STRING = "\x28\xBF\x4E\x5E\x4E\x75\x8A\x41\x64\x00\x4E\x56\xFF\xFA\x01\x08\x2E\x2E\x00\xB6\xD0\x68\x3E\x80\x2F\x0C\xA9\xFE\x64\x53\x69\x7A";
    private const PASSWORD_LENGTH = 32;

    public function __construct(
        #[SensitiveParameter] private readonly ?string $userPassword = null,
        #[SensitiveParameter] private readonly ?string $ownerPassword = null,
    ) {
    }

    /** @see 7.6.4.4.3, 7.6.4.4.4 and 7.6.4.4.5 */
    public function isUserPasswordValid(EncryptDictionary $encryptDictionary, string $firstID): bool {
        $userPasswordEntry = $encryptDictionary->getUserPasswordEntry();
        $securityHandlerRevision = $encryptDictionary->getStandardSecurityHandlerRevision();

        $fileEncryptionKey = $this->getFileEncryptionKey($encryptDictionary, $firstID);
        if ($securityHandlerRevision === StandardSecurityHandlerRevision::v2) { // @see 7.6.4.4.3, step b
            return RC4::encrypt($fileEncryptionKey, self::PADDING_STRING) === $userPasswordEntry;
        }

        if (in_array($securityHandlerRevision, [StandardSecurityHandlerRevision::v3, StandardSecurityHandlerRevision::v4], true)) { // @see 7.6.4.4.4, step b through e
            $hash = md5(self::PADDING_STRING . $firstID, true);
            $encryptedHash = RC4::encrypt($fileEncryptionKey, $hash);
            for ($i = 1; $i <= 19; $i++) {
                $modifiedKey = $fileEncryptionKey;
                for ($j = 0, $length = strlen($modifiedKey); $j < $length; $j++) {
                    $modifiedKey[$j] = $modifiedKey[$j] ^ chr($i);
                }

                $encryptedHash = RC4::encrypt($modifiedKey, $encryptedHash);
            }

            return $encryptedHash === substr($userPasswordEntry, 0, 16);
        }

        throw new NotSupportedException('Unsupported security handler revision: ' . $securityHandlerRevision->value);
    }

    /** @see 7.6.4.3.2 */
    public function getFileEncryptionKey(EncryptDictionary $encryptDictionary, string $firstIDValue): string {
        $fileEncryptionKeyLengthInBytes = ($encryptDictionary->getLengthFileEncryptionKeyInBits() ?? throw new ParseFailureException()) / 8;
        $md5Hash = md5(
            $this->getPaddedUserPassword() // step a+b
            . $encryptDictionary->getOwnerPasswordEntry() // step c
            . pack('V', $encryptDictionary->getPValue()) // step d
            . $firstIDValue, // step e
            true
        );

        if ($encryptDictionary->getStandardSecurityHandlerRevision() === StandardSecurityHandlerRevision::v2) {
            return substr($md5Hash, 0, 5);
        }

        for ($i = 1; $i <= 50; $i++) { // step h
            $md5Hash = md5(substr($md5Hash, 0, $fileEncryptionKeyLengthInBytes), true);
        }

        return substr($md5Hash, 0, $fileEncryptionKeyLengthInBytes);
    }

    /** @see 7.6.4.3.2 step a */
    public function getPaddedUserPassword(): string {
        return substr($this->userPassword ?? '', 0, self::PASSWORD_LENGTH)
            . substr(self::PADDING_STRING, 0, max(0, self::PASSWORD_LENGTH - strlen($this->userPassword ?? '')));
    }
}
