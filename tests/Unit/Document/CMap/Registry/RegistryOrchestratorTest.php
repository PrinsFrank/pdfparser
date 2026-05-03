<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Tests\Unit\Document\CMap\Registry;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use PrinsFrank\PdfParser\Document\CMap\Registry\Adobe\Identity0;
use PrinsFrank\PdfParser\Document\CMap\Registry\RegistryOrchestrator;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Integer\IntegerValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\TextString\TextStringValue;

#[CoversClass(RegistryOrchestrator::class)]
class RegistryOrchestratorTest extends TestCase {
    public function testGetForRegistryOrderingSupplement(): void {
        static::assertEquals(
            new Identity0(),
            RegistryOrchestrator::getForRegistryOrderingSupplement(
                new TextStringValue('(Adobe)'),
                new TextStringValue('(Identity)'),
                new IntegerValue(0),
            ),
        );
        static::assertNull(
            RegistryOrchestrator::getForRegistryOrderingSupplement(
                new TextStringValue('(Adobe)'),
                new TextStringValue('(Identity)'),
                new IntegerValue(1),
            ),
        );
        static::assertNull(
            RegistryOrchestrator::getForRegistryOrderingSupplement(
                new TextStringValue('(Adobe2)'),
                new TextStringValue('(Identity)'),
                new IntegerValue(0),
            ),
        );
        static::assertNull(
            RegistryOrchestrator::getForRegistryOrderingSupplement(
                new TextStringValue('(Adobe)'),
                new TextStringValue('(Identity2)'),
                new IntegerValue(0),
            ),
        );
    }
}
