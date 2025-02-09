<?php
declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Text;

use PrinsFrank\PdfParser\Document\Document;
use PrinsFrank\PdfParser\Document\Object\Decorator\Font;
use PrinsFrank\PdfParser\Document\Object\Decorator\Page;
use PrinsFrank\PdfParser\Document\Text\OperatorString\TextPositioningOperator;
use PrinsFrank\PdfParser\Document\Text\OperatorString\TextShowingOperator;
use PrinsFrank\PdfParser\Document\Text\OperatorString\TextStateOperator;
use PrinsFrank\PdfParser\Exception\ParseFailureException;
use PrinsFrank\PdfParser\Exception\PdfParserException;

class TextObjectCollection {
    /** @var list<TextObject> */
    public readonly array $textObjects;

    /** @no-named-arguments */
    public function __construct(
        TextObject... $textObjects
    ) {
        $this->textObjects = $textObjects;
    }

    /** @throws PdfParserException */
    public function getText(Document $document, Page $page): string {
        $text = '';
        $font = null;
        foreach ($this->textObjects as $textObject) {
            $textObjectText = '';
            foreach ($textObject->textOperators as $textOperator) {
                if ($textOperator->operator instanceof TextPositioningOperator) {
                    $textObjectText .= $textOperator->operator->display($textOperator->operands);
                } elseif ($textOperator->operator instanceof TextShowingOperator) {
                    if ($font === null) {
                        throw new ParseFailureException('A font should be selected before being used');
                    }

                    $textObjectText .= $textOperator->operator->displayOperands($textOperator->operands, $font);
                } elseif ($textOperator->operator === TextStateOperator::FONT_SIZE) {
                    if (($fontDictionary = $page->getFontDictionary()) === null) {
                        throw new ParseFailureException('No font dictionary available');
                    }

                    $font = $fontDictionary->getObjectForReference($document, $fontReference = $textOperator->operator->getFontReference($textOperator->operands), Font::class)
                        ?? throw new ParseFailureException(sprintf('Unable to locate font with reference "/%s"', $fontReference->value));
                }
            }

            if (trim($textObjectText) !== '') {
                $text .= ' ' . trim($textObjectText);
            }
        }

        return preg_replace('/\h+([.,!?])/', '$1', str_replace('  ', ' ', trim($text))) ?? throw new ParseFailureException();
    }
}
