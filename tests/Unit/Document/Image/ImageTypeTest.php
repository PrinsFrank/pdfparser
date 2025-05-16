<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Tests\Unit\Document\Image;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use PrinsFrank\PdfParser\Document\Image\ImageType;

#[CoversClass(ImageType::class)]
class ImageTypeTest extends TestCase {
    #[DataProvider('cases')]
    public function testGetFileExtensionCanBeCalledForAllImageTypes(ImageType $imageType): void {
        /** @phpstan-ignore method.resultUnused */
        $imageType->getFileExtension();
    }

    /** @return iterable<array{0: ImageType}> */
    public static function cases(): iterable {
        foreach (ImageType::cases() as $imageType) {
            yield [$imageType];
        }
    }
}
