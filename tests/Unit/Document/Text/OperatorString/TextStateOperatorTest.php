<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Tests\Unit\Document\Text\OperatorString;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use PrinsFrank\PdfParser\Document\ContentStream\Command\Operator\State\TextStateOperator;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryKey\DictionaryKey;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryKey\ExtendedDictionaryKey;
use PrinsFrank\PdfParser\Exception\InvalidArgumentException;

#[CoversClass(TextStateOperator::class)]
class TextStateOperatorTest extends TestCase {
    public function testGetFontReference(): void {
        static::assertEquals(
            new ExtendedDictionaryKey('F12'),
            TextStateOperator::FONT_SIZE->getFontReference('/F12 1'),
        );
        static::assertEquals(
            DictionaryKey::F,
            TextStateOperator::FONT_SIZE->getFontReference('/F 1'),
        );
    }

    public function testGetFontReferenceThrowsExceptionForNonFontSizeOperator(): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Can only retrieve font for Tf operator');
        TextStateOperator::WORD_SPACE->getFontReference('');
    }

    public function testGetFontReferenceThrowsExceptionInvalidFontOperand(): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid font operand "foo"');
        TextStateOperator::FONT_SIZE->getFontReference('foo');
    }
}
