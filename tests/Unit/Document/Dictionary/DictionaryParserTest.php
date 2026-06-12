<?php
declare(strict_types=1);

namespace PrinsFrank\PdfParser\Tests\Unit\Document\Dictionary;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use PrinsFrank\PdfParser\Document\Dictionary\Dictionary;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryEntry\DictionaryEntry;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryKey\DictionaryKey;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryKey\ExtendedDictionaryKey;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryParser;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Array\ArrayValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Array\DictionaryArrayValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Array\CrossReferenceStreamByteSizes;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Boolean\BooleanValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Date\DateValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Integer\IntegerValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Name\EventNameValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Name\FilterNameValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Name\PageModeNameValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Name\TabsNameValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Name\TrappedNameValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Name\TypeNameValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Rectangle\Rectangle;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Reference\ReferenceValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Reference\ReferenceValueArray;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\TextString\TextStringValue;
use PrinsFrank\PdfParser\Document\Version\Version;
use PrinsFrank\PdfParser\Stream\InMemoryStream;
use ValueError;

#[CoversClass(DictionaryParser::class)]
class DictionaryParserTest extends TestCase {
    public function testParseCrossReference(): void {
        $stream = new InMemoryStream(
            <<<EOD
            15 0 obj
            <<
            /Type /XRef
            /Index [0 16]
            /Size 16
            /W [1 2 1]
            /Root 13 0 R
            /Info 14 0 R
            /ID [<F7F55EED423E47B1F3E311DE7CFCE2E5> <F7F55EED423E47B1F3E311DE7CFCE2E5>]
            /Length 57
            /Filter /FlateDecode
            >>
            stream
            EOD,
        );
        static::assertEquals(
            new Dictionary(
                new DictionaryEntry(DictionaryKey::TYPE, TypeNameValue::X_REF),
                new DictionaryEntry(DictionaryKey::INDEX, new ArrayValue([0, 16])),
                new DictionaryEntry(DictionaryKey::SIZE, new IntegerValue(16)),
                new DictionaryEntry(DictionaryKey::W, new CrossReferenceStreamByteSizes(1, 2, 1)),
                new DictionaryEntry(DictionaryKey::ROOT, new ReferenceValue(13, 0)),
                new DictionaryEntry(DictionaryKey::INFO, new ReferenceValue(14, 0)),
                new DictionaryEntry(DictionaryKey::ID, new ArrayValue(['<F7F55EED423E47B1F3E311DE7CFCE2E5>', '<F7F55EED423E47B1F3E311DE7CFCE2E5>'])),
                new DictionaryEntry(DictionaryKey::LENGTH, new IntegerValue(57)),
                new DictionaryEntry(DictionaryKey::FILTER, FilterNameValue::FLATE_DECODE),
            ),
            DictionaryParser::parse(null, $stream, 0, $stream->getSizeInBytes()),
        );
    }

    public function testParseCrossReferencePaddedArrayValues(): void {
        $stream = new InMemoryStream(
            <<<EOD
            <<
            /Index [ 0 16 ]
            >>
            EOD,
        );
        static::assertEquals(
            new Dictionary(
                new DictionaryEntry(DictionaryKey::INDEX, new ArrayValue([0, 16])),
            ),
            DictionaryParser::parse(null, $stream, 0, $stream->getSizeInBytes()),
        );
    }

    public function testObjectStream(): void {
        $stream = new InMemoryStream(
            '<<
                /DecodeParms
                        <<
                            /Columns 5
                            /Predictor 12
                        >>
                /Filter/FlateDecode
                /ID[<9A27A23F6A2546448EBB340FF38477BD><C5C4714E306446ABAE40FE784477D322>]
                /Index[2460 1 4311 1 4317 2 4414 1 4717 21]
                /Info 4318 0 R
                /Length 106
                /Prev 46153797
                /Root 4320 0 R
                /Size 4738
                /Type/XRef
                /W[1 4 0]
            >>stream',
        );
        static::assertEquals(
            new Dictionary(
                new DictionaryEntry(DictionaryKey::DECODE_PARMS, new Dictionary(
                    new DictionaryEntry(DictionaryKey::COLUMNS, new IntegerValue(5)),
                    new DictionaryEntry(DictionaryKey::PREDICTOR, new IntegerValue(12)),
                )),
                new DictionaryEntry(DictionaryKey::FILTER, FilterNameValue::FLATE_DECODE),
                new DictionaryEntry(DictionaryKey::ID, new ArrayValue(['<9A27A23F6A2546448EBB340FF38477BD>', '<C5C4714E306446ABAE40FE784477D322>'])),
                new DictionaryEntry(DictionaryKey::INDEX, new ArrayValue([2460, 1, 4311, 1, 4317, 2, 4414, 1, 4717, 21])),
                new DictionaryEntry(DictionaryKey::INFO, new ReferenceValue(4318, 0)),
                new DictionaryEntry(DictionaryKey::LENGTH, new IntegerValue(106)),
                new DictionaryEntry(DictionaryKey::PREV, new IntegerValue(46153797)),
                new DictionaryEntry(DictionaryKey::ROOT, new ReferenceValue(4320, 0)),
                new DictionaryEntry(DictionaryKey::SIZE, new IntegerValue(4738)),
                new DictionaryEntry(DictionaryKey::TYPE, TypeNameValue::X_REF),
                new DictionaryEntry(DictionaryKey::W, new CrossReferenceStreamByteSizes(1, 4, 0)),
            ),
            DictionaryParser::parse(null, $stream, 0, $stream->getSizeInBytes()),
        );
    }

    public function testParseSingleLine(): void {
        $stream = new InMemoryStream('<</DecodeParms<</Columns 5/Predictor 12>>/Filter/FlateDecode/ID[<9A27A23F6A2546448EBB340FF38477BD><C5C4714E306446ABAE40FE784477D322>]/Index[2460 1 4311 1 4317 2 4414 1 4717 21]/Info 4318 0 R/Length 106/Prev 46153797/Root 4320 0 R/Size 4738/Type/XRef/W[1 4 0]>>stream');
        static::assertEquals(
            new Dictionary(
                new DictionaryEntry(DictionaryKey::DECODE_PARMS, new Dictionary(
                    new DictionaryEntry(DictionaryKey::COLUMNS, new IntegerValue(5)),
                    new DictionaryEntry(DictionaryKey::PREDICTOR, new IntegerValue(12)),
                )),
                new DictionaryEntry(DictionaryKey::FILTER, FilterNameValue::FLATE_DECODE),
                new DictionaryEntry(DictionaryKey::ID, new ArrayValue(['<9A27A23F6A2546448EBB340FF38477BD>', '<C5C4714E306446ABAE40FE784477D322>'])),
                new DictionaryEntry(DictionaryKey::INDEX, new ArrayValue([2460, 1, 4311, 1, 4317, 2, 4414, 1, 4717, 21])),
                new DictionaryEntry(DictionaryKey::INFO, new ReferenceValue(4318, 0)),
                new DictionaryEntry(DictionaryKey::LENGTH, new IntegerValue(106)),
                new DictionaryEntry(DictionaryKey::PREV, new IntegerValue(46153797)),
                new DictionaryEntry(DictionaryKey::ROOT, new ReferenceValue(4320, 0)),
                new DictionaryEntry(DictionaryKey::SIZE, new IntegerValue(4738)),
                new DictionaryEntry(DictionaryKey::TYPE, TypeNameValue::X_REF),
                new DictionaryEntry(DictionaryKey::W, new CrossReferenceStreamByteSizes(1, 4, 0)),
            ),
            DictionaryParser::parse(null, $stream, 0, $stream->getSizeInBytes()),
        );
    }

    public function testParseFontInfo(): void {
        $stream = new InMemoryStream(
            <<<EOD
            <<
            /Type /FontDescriptor
            /FontName /TAIPAH+CMR10
            /Flags 4
            /FontBBox [-40 -250 1009 750]
            /Ascent 694
            /CapHeight 683
            /Descent -194
            /ItalicAngle 0
            /StemV 69
            /XHeight 431
            /CharSet (/S/a/c/d/e/fi/g/l/n/o/one/p/r/s/t/two)
            /FontFile 11 0 R
            >>
            EOD,
        );
        static::assertEquals(
            new Dictionary(
                new DictionaryEntry(DictionaryKey::TYPE, TypeNameValue::FONT_DESCRIPTOR),
                new DictionaryEntry(DictionaryKey::FONT_NAME, new TextStringValue('/TAIPAH+CMR10')),
                new DictionaryEntry(DictionaryKey::FLAGS, new IntegerValue(4)),
                new DictionaryEntry(DictionaryKey::FONT_BBOX, new Rectangle(-40, -250, 1009, 750)),
                new DictionaryEntry(DictionaryKey::ASCENT, new IntegerValue(694)),
                new DictionaryEntry(DictionaryKey::CAP_HEIGHT, new IntegerValue(683)),
                new DictionaryEntry(DictionaryKey::DESCENT, new IntegerValue(-194)),
                new DictionaryEntry(DictionaryKey::ITALIC_ANGLE, new IntegerValue(0)),
                new DictionaryEntry(DictionaryKey::STEM_V, new IntegerValue(69)),
                new DictionaryEntry(DictionaryKey::XHEIGHT, new IntegerValue(431)),
                new DictionaryEntry(DictionaryKey::CHAR_SET, new TextStringValue('(/S/a/c/d/e/fi/g/l/n/o/one/p/r/s/t/two)')),
                new DictionaryEntry(DictionaryKey::FONT_FILE, new ReferenceValue(11, 0)),
            ),
            DictionaryParser::parse(null, $stream, 0, $stream->getSizeInBytes()),
        );
    }

    /** @throws ValueError */
    public function testParseValuesEncapsulatedInParentheses(): void {
        $stream = new InMemoryStream(
            <<<EOD
            <<
            /Producer (pdfTeX-1.40.18)
            /Creator (TeX)
            /CreationDate (D:20220506201153+02'00')
            /ModDate (D:20220506201153+02'00')
            /Trapped /False
            /PTEX.Fullbanner (This is pdfTeX, Version 3.14159265-2.6-1.40.18 (TeX Live 2017/Debian) kpathsea version 6.2.3)
            >>
            EOD,
        );
        static::assertNotFalse($creationModificationDate = DateTimeImmutable::createFromFormat('Y-m-d H:i:s P', '2022-05-06 20:11:53 +02:00'));
        static::assertEquals(
            new Dictionary(
                new DictionaryEntry(DictionaryKey::PRODUCER, new TextStringValue('(pdfTeX-1.40.18)')),
                new DictionaryEntry(DictionaryKey::CREATOR, new TextStringValue('(TeX)')),
                new DictionaryEntry(DictionaryKey::CREATION_DATE, new DateValue($creationModificationDate)),
                new DictionaryEntry(DictionaryKey::MOD_DATE, new DateValue($creationModificationDate)),
                new DictionaryEntry(DictionaryKey::TRAPPED, TrappedNameValue::FALSE),
                new DictionaryEntry(DictionaryKey::PTEX_FULLBANNER, new TextStringValue('(This is pdfTeX, Version 3.14159265-2.6-1.40.18 (TeX Live 2017/Debian) kpathsea version 6.2.3)')),
            ),
            DictionaryParser::parse(null, $stream, 0, $stream->getSizeInBytes()),
        );
    }

    public function testIgnoreCommentedLines(): void {
        $stream = new InMemoryStream(
            <<<EOD
            <<
            /Producer (pdfTeX-1.40.18)
            %/Creator (TeX)
            %  /Creator (TeX)
            /Foo (Bar)
            /Bar (% this is not a comment but a string literal)
            >>
            EOD,
        );
        static::assertEquals(
            new Dictionary(
                new DictionaryEntry(DictionaryKey::PRODUCER, new TextStringValue('(pdfTeX-1.40.18)')),
                new DictionaryEntry(new ExtendedDictionaryKey('Foo'), new TextStringValue('(Bar)')),
                new DictionaryEntry(new ExtendedDictionaryKey('Bar'), new TextStringValue('(% this is not a comment but a string literal)')),
            ),
            DictionaryParser::parse(null, $stream, 0, $stream->getSizeInBytes()),
        );
    }

    public function testHandlesNumsNumberTree(): void {
        $stream = new InMemoryStream(
            <<<EOD
            <<
            /OpenAction[3 0 R/Fit]
            /PageMode/UseOutlines
            /PageLabels<</Nums[0<</S/r>>12<</S/D>>]>>
            /Names 13164 0 R
            /Outlines 13165 0 R
            /Pages 13221 0 R
            /Type/Catalog
            >>
            EOD,
        );
        static::assertEquals(
            new Dictionary(
                new DictionaryEntry(DictionaryKey::OPEN_ACTION, new ArrayValue([3, 0, 'R', '/Fit'])),
                new DictionaryEntry(DictionaryKey::PAGE_MODE, PageModeNameValue::USE_OUTLINES),
                new DictionaryEntry(DictionaryKey::PAGE_LABELS, new Dictionary(
                    new DictionaryEntry(DictionaryKey::NUMS, new TextStringValue('[0<</S/r>>12<</S/D>>]')),
                )),
                new DictionaryEntry(DictionaryKey::NAMES, new ReferenceValue(13164, 0)),
                new DictionaryEntry(DictionaryKey::OUTLINES, new ReferenceValue(13165, 0)),
                new DictionaryEntry(DictionaryKey::PAGES, new ReferenceValue(13221, 0)),
                new DictionaryEntry(DictionaryKey::TYPE, TypeNameValue::CATALOG),
            ),
            DictionaryParser::parse(null, $stream, 0, $stream->getSizeInBytes()),
        );
    }

    public function testHandlesGraphicStateSubDictionaries(): void {
        $stream = new InMemoryStream(
            <<<EOD
            <</Type /Page
            /Resources <<
                /ProcSet [/PDF /Text /ImageB /ImageC /ImageI]
                /ExtGState <</G3 3 0 R>>
                /Font <</F4 4 0 R>>
            >>
            /MediaBox [0 0 596 842]
            /Contents 5 0 R
            /StructParents 0
            /Tabs /S
            /Parent 6 0 R>>
            EOD,
        );
        static::assertEquals(
            new Dictionary(
                new DictionaryEntry(DictionaryKey::TYPE, TypeNameValue::PAGE),
                new DictionaryEntry(DictionaryKey::RESOURCES, new Dictionary(
                    new DictionaryEntry(DictionaryKey::PROC_SET, new ArrayValue(['/PDF', '/Text', '/ImageB', '/ImageC', '/ImageI'])),
                    new DictionaryEntry(DictionaryKey::EXT_GSTATE, new Dictionary(
                        new DictionaryEntry(new ExtendedDictionaryKey('G3'), new ReferenceValue(3, 0)),
                    )),
                    new DictionaryEntry(DictionaryKey::FONT, new Dictionary(
                        new DictionaryEntry(new ExtendedDictionaryKey('F4'), new ReferenceValue(4, 0)),
                    )),
                )),
                new DictionaryEntry(DictionaryKey::MEDIA_BOX, new Rectangle(0.0, 0.0, 596.0, 842.0)),
                new DictionaryEntry(DictionaryKey::CONTENTS, new ReferenceValue(5, 0)),
                new DictionaryEntry(DictionaryKey::STRUCT_PARENTS, new IntegerValue(0)),
                new DictionaryEntry(DictionaryKey::TABS, TabsNameValue::StructureOrder),
                new DictionaryEntry(DictionaryKey::PARENT, new ReferenceValue(6, 0)),
            ),
            DictionaryParser::parse(null, $stream, 0, $stream->getSizeInBytes()),
        );
    }

    public function testHandlesNestedArrays(): void {
        $stream = new InMemoryStream(
            <<<EOD
                <<
                    /OCProperties <<
                        /D <<
                            /AS [
                                <<
                                    /Category [/Print]
                                    /Event/Print
                                    /OCGs [939 0 R 419 0 R]
                                >>
                                <<
                                    /Category [/View]
                                    /Event/View
                                    /OCGs[939 0 R 419 0 R]
                                >>
                            ]
                            /BaseState/OFF
                            /ON[419 0 R]
                            /Order[]
                            /RBGroups[]
                        >>
                        /OCGs[939 0 R 419 0 R]
                    >>
                >>
            EOD,
        );
        static::assertEquals(
            new Dictionary(
                new DictionaryEntry(
                    DictionaryKey::OCPROPERTIES,
                    new Dictionary(
                        new DictionaryEntry(
                            DictionaryKey::D,
                            new Dictionary(
                                new DictionaryEntry(
                                    DictionaryKey::AS,
                                    new DictionaryArrayValue(
                                        new Dictionary(
                                            new DictionaryEntry(
                                                DictionaryKey::CATEGORY,
                                                new ArrayValue(['/View']),
                                            ),
                                            new DictionaryEntry(
                                                DictionaryKey::EVENT,
                                                EventNameValue::View,
                                            ),
                                            new DictionaryEntry(
                                                DictionaryKey::OCGS,
                                                new ReferenceValueArray(
                                                    new ReferenceValue(939, 0),
                                                    new ReferenceValue(419, 0),
                                                ),
                                            ),
                                        ),
                                    ),
                                ),
                                new DictionaryEntry(
                                    DictionaryKey::BASE_STATE,
                                    new TextStringValue('/OFF'),
                                ),
                                new DictionaryEntry(
                                    DictionaryKey::ON,
                                    new ReferenceValueArray(
                                        new ReferenceValue(419, 0),
                                    ),
                                ),
                                new DictionaryEntry(
                                    DictionaryKey::ORDER,
                                    new ArrayValue([]),
                                ),
                                new DictionaryEntry(
                                    DictionaryKey::RBGROUPS,
                                    new ArrayValue([]),
                                ),
                            ),
                        ),
                        new DictionaryEntry(
                            DictionaryKey::OCGS,
                            new ReferenceValueArray(
                                new ReferenceValue(939, 0),
                                new ReferenceValue(419, 0),
                            ),
                        ),
                    ),
                ),
            ),
            DictionaryParser::parse(null, $stream, 0, $stream->getSizeInBytes()),
        );
    }

    public function testHandlesValueOnNewLine(): void {
        $stream = new InMemoryStream(
            <<<EOD
            << /Filter
            /DCTDecode >>
            EOD,
        );
        static::assertEquals(
            new Dictionary(
                new DictionaryEntry(DictionaryKey::FILTER, FilterNameValue::DCT_DECODE),
            ),
            DictionaryParser::parse(null, $stream, 0, $stream->getSizeInBytes()),
        );

        // Note the extra space after the /Filter
        $stream = new InMemoryStream(
            <<<EOD
            << /Filter
            /DCTDecode >>
            EOD,
        );
        static::assertEquals(
            new Dictionary(
                new DictionaryEntry(DictionaryKey::FILTER, FilterNameValue::DCT_DECODE),
            ),
            DictionaryParser::parse(null, $stream, 0, $stream->getSizeInBytes()),
        );
    }

    public function testHandlesKeyValuePairWithTrailingComments(): void {
        $stream = new InMemoryStream(
            <<<EOD
                                 % some trailing comments?
            <<                   %
                /Type /Catalog   %
                /Pages 3 0 R     %
            >>
            EOD,
        );
        static::assertEquals(
            new Dictionary(
                new DictionaryEntry(DictionaryKey::TYPE, TypeNameValue::CATALOG),
                new DictionaryEntry(DictionaryKey::PAGES, new ReferenceValue(3, 0)),
            ),
            DictionaryParser::parse(null, $stream, 0, $stream->getSizeInBytes()),
        );
    }

    public function testHandlesKeyValuePairWithCommentBetweenKeyAndValue(): void {
        $stream = new InMemoryStream(
            <<<EOD
            << /Type %
             /Catalog %
             /Pages %
             3 0 R %
            >>
            EOD,
        );
        static::assertEquals(
            new Dictionary(
                new DictionaryEntry(DictionaryKey::TYPE, TypeNameValue::CATALOG),
                new DictionaryEntry(DictionaryKey::PAGES, new ReferenceValue(3, 0)),
            ),
            DictionaryParser::parse(null, $stream, 0, $stream->getSizeInBytes()),
        );
    }

    public function testHandlesComplexNestedObjectsOnSingleLine(): void {
        $stream = new InMemoryStream(
            <<<PDF
            <</AcroForm<</DA(/Helv 0 Tf 0 g )/DR<</Encoding<</PDFDocEncoding 50 0 R>>/Font<</Helv 48 0 R/ZaDb 49 0 R>>/ProcSet[/PDF/Text/ImageB/ImageC/ImageI]/XObject<</FRM 31 0 R>>>>/Fields[30 0 R]/SigFlags 3>>/DSS 40 0 R/Extensions<</ADBE<</BaseVersion/1.7/ExtensionLevel 5>>>>/Lang(de)/MarkInfo<</Marked true>>/Metadata 27 0 R/Pages 2 0 R/StructTreeRoot 15 0 R/Type/Catalog/Version/1.7/ViewerPreferences 28 0 R>>
            PDF,
        );

        static::assertEquals(
            new Dictionary(
                new DictionaryEntry(
                    DictionaryKey::ACRO_FORM,
                    new Dictionary(
                        new DictionaryEntry(
                            DictionaryKey::DA,
                            new TextStringValue('(/Helv 0 Tf 0 g )'),
                        ),
                        new DictionaryEntry(
                            DictionaryKey::DR,
                            new Dictionary(
                                new DictionaryEntry(
                                    DictionaryKey::ENCODING,
                                    new Dictionary(
                                        new DictionaryEntry(
                                            DictionaryKey::PDFDOC_ENCODING,
                                            new ReferenceValue(50, 0),
                                        ),
                                    ),
                                ),
                                new DictionaryEntry(
                                    DictionaryKey::FONT,
                                    new Dictionary(
                                        new DictionaryEntry(
                                            DictionaryKey::HELV,
                                            new ReferenceValue(48, 0),
                                        ),
                                        new DictionaryEntry(
                                            DictionaryKey::ZA_DB,
                                            new ReferenceValue(49, 0),
                                        ),
                                    ),
                                ),
                                new DictionaryEntry(
                                    DictionaryKey::PROC_SET,
                                    new ArrayValue(['/PDF', '/Text', '/ImageB', '/ImageC', '/ImageI']),
                                ),
                                new DictionaryEntry(
                                    DictionaryKey::XOBJECT,
                                    new Dictionary(
                                        new DictionaryEntry(
                                            new ExtendedDictionaryKey('FRM'),
                                            new ReferenceValue(31, 0),
                                        ),
                                    ),
                                ),
                            ),
                        ),
                        new DictionaryEntry(
                            DictionaryKey::FIELDS,
                            new ReferenceValueArray(
                                new ReferenceValue(30, 0),
                            ),
                        ),
                        new DictionaryEntry(
                            DictionaryKey::SIG_FLAGS,
                            new IntegerValue(3),
                        ),
                    ),
                ),
                new DictionaryEntry(
                    new ExtendedDictionaryKey('DSS'),
                    new ReferenceValue(40, 0),
                ),
                new DictionaryEntry(
                    DictionaryKey::EXTENSIONS,
                    new Dictionary(
                        new DictionaryEntry(
                            new ExtendedDictionaryKey('ADBE'),
                            new Dictionary(
                                new DictionaryEntry(
                                    DictionaryKey::BASE_VERSION,
                                    Version::V1_7,
                                ),
                                new DictionaryEntry(
                                    DictionaryKey::EXTENSION_LEVEL,
                                    new IntegerValue(5),
                                ),
                            ),
                        ),
                    ),
                ),
                new DictionaryEntry(
                    DictionaryKey::LANG,
                    new TextStringValue('(de)'),
                ),
                new DictionaryEntry(
                    DictionaryKey::MARK_INFO,
                    new Dictionary(
                        new DictionaryEntry(
                            DictionaryKey::MARKED,
                            new BooleanValue(true),
                        ),
                    ),
                ),
                new DictionaryEntry(
                    DictionaryKey::METADATA,
                    new ReferenceValue(27, 0),
                ),
                new DictionaryEntry(
                    DictionaryKey::PAGES,
                    new ReferenceValue(2, 0),
                ),
                new DictionaryEntry(
                    DictionaryKey::STRUCT_TREE_ROOT,
                    new ReferenceValue(15, 0),
                ),
                new DictionaryEntry(
                    DictionaryKey::TYPE,
                    TypeNameValue::CATALOG,
                ),
                new DictionaryEntry(
                    DictionaryKey::VERSION,
                    Version::V1_7,
                ),
                new DictionaryEntry(
                    DictionaryKey::VIEWER_PREFERENCES,
                    new ReferenceValue(28, 0),
                ),
            ),
            DictionaryParser::parse(null, $stream, 0, $stream->getSizeInBytes()),
        );
    }
}
