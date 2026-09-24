<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Tests\Feature;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use PrinsFrank\PdfParser\PdfParser;

#[CoversNothing]
class AFIndirectReferenceArrayTest extends TestCase {
    public function testFileSpecificationsFromIndirectAFArrayWithSurroundingWhitespace(): void {
        $document = (new PdfParser())
            ->parseFile(__DIR__ . '/samples/af-indirect-reference-array.pdf');

        $fileSpecifications = $document->getCatalog()->getFileSpecifications();

        static::assertCount(1, $fileSpecifications);
        static::assertSame('factur-x.xml', $fileSpecifications[0]->getFileSpecificationString());
        static::assertSame(
            '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<rsm:CrossIndustryInvoice/>' . "\n",
            $fileSpecifications[0]->getEmbeddedFile()?->getStream()->toString(),
        );
    }
}
