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

/** @api */
class ContentStream {
    /** @var list<TextObject|ContentStreamCommand> */
    public readonly array $content;

    /** @no-named-arguments */
    public function __construct(
        TextObject|ContentStreamCommand... $content
    ) {
        $this->content = $content;
    }

    /** @throws PdfParserException */
    public function getText(Document $document, Page $page): string {
        $text = '';
        $font = null;
        foreach ($this->content as $content) {
            $textObjectText = '';
            if (!$content instanceof TextObject) {
                continue;
            }

            foreach ($content->contentStreamCommands as $contentStreamCommand) {
                if ($contentStreamCommand->operator instanceof TextPositioningOperator) {
                    $textObjectText .= $contentStreamCommand->operator->display($contentStreamCommand->operands);
                } elseif ($contentStreamCommand->operator instanceof TextShowingOperator) {
                    if ($font === null) {
                        throw new ParseFailureException('A font should be selected before being used');
                    }

                    $textObjectText .= $contentStreamCommand->operator->displayOperands($contentStreamCommand->operands, $font);
                } elseif ($contentStreamCommand->operator === TextStateOperator::FONT_SIZE) {
                    if (($fontDictionary = $page->getFontDictionary()) === null) {
                        throw new ParseFailureException('No font dictionary available');
                    }

                    $font = $fontDictionary->getObjectForReference($document, $fontReference = $contentStreamCommand->operator->getFontReference($contentStreamCommand->operands), Font::class)
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
