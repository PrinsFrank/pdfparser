<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Tests\Unit\Document\Text;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryKey\ExtendedDictionaryKey;
use PrinsFrank\PdfParser\Document\Text\Positioning\PositionedTextElement;
use PrinsFrank\PdfParser\Document\Text\Positioning\TextMatrix;
use PrinsFrank\PdfParser\Document\Text\Positioning\TextState;
use PrinsFrank\PdfParser\Document\Text\TextObjectCollection;
use PrinsFrank\PdfParser\Document\Text\TextParser;

#[CoversClass(TextObjectCollection::class)]
class TextObjectCollectionTest  extends TestCase {
    public function testGetPositionedTextElements(): void {
        $textObjectString = <<<EOD
            1 0 0 -1 0 842 cm
            q
            .75 0 0 .75 0 0 cm
            1 1 1 RG 1 1 1 rg
            /G3 gs
            0 0 794 1123 re
            f
            Q
            q
            .75 0 0 .75 72 72 cm
            0 0 0 RG 0 0 0 rg
            /G3 gs
            /P <</MCID 0 >>BDC
            BT
            /F4 14.666667 Tf
            1 0 0 -1 0 .47981739 Tm
            0 -13.2773438 Td <0024> Tj
            9.7756042 0 Td <0025> Tj
            9.7756042 0 Td <0026> Tj
            ET
            BT
            /F4 14.666667 Tf
            1 0 0 -1 0 .47981739 Tm
            30.135483 -13.2773438 Td <0003> Tj
            ET
            Q
            q
            .75 0 0 .75 72 86.546265 cm
            0 0 0 RG 0 0 0 rg
            /G3 gs
            EMC
            /P <</MCID 1 >>BDC
            BT
            /F4 14.666667 Tf
            1 0 0 -1 0 .47981739 Tm
            0 -13.2773438 Td <0027> Tj
            10.5842743 0 Td <0028> Tj
            9.7756042 0 Td <0029> Tj
            ET
            BT
            /F4 14.666667 Tf
            1 0 0 -1 0 .47981739 Tm
            29.3125 -13.2773438 Td <0003> Tj
            ET
            Q
            q
            .75 0 0 .75 72 101.092529 cm
            0 0 0 RG 0 0 0 rg
            /G3 gs
            EMC
            /P <</MCID 2 >>BDC
            BT
            /Span<</ActualText <FEFF200B> >> BDC
            /F4 14.666667 Tf
            1 0 0 -1 0 .47981739 Tm
            0 -13.2773438 Td <0003> Tj
            EMC
            ET
            BT
            /Span<</ActualText <FEFF200B> >> BDC
            /F4 14.666667 Tf
            1 0 0 -1 0 .47981739 Tm
            48 -13.2773438 Td <0003> Tj
            EMC
            ET
            BT
            /F4 14.666667 Tf
            1 0 0 -1 0 .47981739 Tm
            96 -13.2773438 Td <002A> Tj
            11.4001007 0 Td <002B> Tj
            10.5842743 0 Td <002C> Tj
            ET
            BT
            /F4 14.666667 Tf
            1 0 0 -1 0 .47981739 Tm
            122.056351 -13.2773438 Td <0003> Tj
            4.0719757 0 Td <0003> Tj
            4.0719757 0 Td <0003> Tj
            4.0719757 0 Td <0003> Tj
            4.0719757 0 Td <0003> Tj
            4.0719757 0 Td <0003> Tj
            ET
            BT
            /F4 14.666667 Tf
            1 0 0 -1 0 .47981739 Tm
            146.488205 -13.2773438 Td <002D> Tj
            7.328125 0 Td <002E> Tj
            9.7756042 0 Td <002F> Tj
            ET
            BT
            /F4 14.666667 Tf
            1 0 0 -1 0 .47981739 Tm
            171.74304 -13.2773438 Td <0003> Tj
            ET
            Q
            q
            .75 0 0 .75 72 115.638794 cm
            0 0 0 RG 0 0 0 rg
            /G3 gs
            EMC
            /P <</MCID 3 >>BDC
            BT
            /F4 14.666667 Tf
            1 0 0 -1 0 .47981739 Tm
            0 -13.2773438 Td <0003> Tj
            ET
            Q
            EMC
        EOD;
        static::assertEquals(
            [
                new PositionedTextElement('<0024>', new TextMatrix(1, 0, 0, 1, 0, 0), new TextState(new ExtendedDictionaryKey('F4'), 14.666667)),
                new PositionedTextElement('<0025>', new TextMatrix(1, 0, 0, 1, 0, 0), new TextState(new ExtendedDictionaryKey('F4'), 14.666667)),
                new PositionedTextElement('<0026>', new TextMatrix(1, 0, 0, 1, 0, 0), new TextState(new ExtendedDictionaryKey('F4'), 14.666667)),
                new PositionedTextElement('<0003>', new TextMatrix(1, 0, 0, 1, 0, 0), new TextState(new ExtendedDictionaryKey('F4'), 14.666667)),
                new PositionedTextElement('<0027>', new TextMatrix(1, 0, 0, 1, 0, 0), new TextState(new ExtendedDictionaryKey('F4'), 14.666667)),
                new PositionedTextElement('<0028>', new TextMatrix(1, 0, 0, 1, 0, 0), new TextState(new ExtendedDictionaryKey('F4'), 14.666667)),
                new PositionedTextElement('<0029>', new TextMatrix(1, 0, 0, 1, 0, 0), new TextState(new ExtendedDictionaryKey('F4'), 14.666667)),
                new PositionedTextElement('<0003>', new TextMatrix(1, 0, 0, 1, 0, 0), new TextState(new ExtendedDictionaryKey('F4'), 14.666667)),
                new PositionedTextElement('<0003>', new TextMatrix(1, 0, 0, 1, 0, 0), new TextState(new ExtendedDictionaryKey('F4'), 14.666667)),
                new PositionedTextElement('<0003>', new TextMatrix(1, 0, 0, 1, 0, 0), new TextState(new ExtendedDictionaryKey('F4'), 14.666667)),
                new PositionedTextElement('<002A>', new TextMatrix(1, 0, 0, 1, 0, 0), new TextState(new ExtendedDictionaryKey('F4'), 14.666667)),
                new PositionedTextElement('<002B>', new TextMatrix(1, 0, 0, 1, 0, 0), new TextState(new ExtendedDictionaryKey('F4'), 14.666667)),
                new PositionedTextElement('<002C>', new TextMatrix(1, 0, 0, 1, 0, 0), new TextState(new ExtendedDictionaryKey('F4'), 14.666667)),
                new PositionedTextElement('<0003>', new TextMatrix(1, 0, 0, 1, 0, 0), new TextState(new ExtendedDictionaryKey('F4'), 14.666667)),
                new PositionedTextElement('<0003>', new TextMatrix(1, 0, 0, 1, 0, 0), new TextState(new ExtendedDictionaryKey('F4'), 14.666667)),
                new PositionedTextElement('<0003>', new TextMatrix(1, 0, 0, 1, 0, 0), new TextState(new ExtendedDictionaryKey('F4'), 14.666667)),
                new PositionedTextElement('<0003>', new TextMatrix(1, 0, 0, 1, 0, 0), new TextState(new ExtendedDictionaryKey('F4'), 14.666667)),
                new PositionedTextElement('<0003>', new TextMatrix(1, 0, 0, 1, 0, 0), new TextState(new ExtendedDictionaryKey('F4'), 14.666667)),
                new PositionedTextElement('<0003>', new TextMatrix(1, 0, 0, 1, 0, 0), new TextState(new ExtendedDictionaryKey('F4'), 14.666667)),
                new PositionedTextElement('<002D>', new TextMatrix(1, 0, 0, 1, 0, 0), new TextState(new ExtendedDictionaryKey('F4'), 14.666667)),
                new PositionedTextElement('<002E>', new TextMatrix(1, 0, 0, 1, 0, 0), new TextState(new ExtendedDictionaryKey('F4'), 14.666667)),
                new PositionedTextElement('<002F>', new TextMatrix(1, 0, 0, 1, 0, 0), new TextState(new ExtendedDictionaryKey('F4'), 14.666667)),
                new PositionedTextElement('<0003>', new TextMatrix(1, 0, 0, 1, 0, 0), new TextState(new ExtendedDictionaryKey('F4'), 14.666667)),
                new PositionedTextElement('<0003>', new TextMatrix(1, 0, 0, 1, 0, 0), new TextState(new ExtendedDictionaryKey('F4'), 14.666667)),
            ],
            TextParser::parse($textObjectString)->getPositionedTextElements(),
        );
    }
}
