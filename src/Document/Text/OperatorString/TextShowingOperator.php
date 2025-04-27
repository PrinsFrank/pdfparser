<?php
declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Text\OperatorString;

use PrinsFrank\PdfParser\Document\Object\Decorator\Font;
use PrinsFrank\PdfParser\Document\Text\Positioning\TextState;
use PrinsFrank\PdfParser\Document\Text\Positioning\TextMatrix;
use PrinsFrank\PdfParser\Exception\ParseFailureException;
use function PHPUnit\Framework\exactly;

/** @internal */
enum TextShowingOperator: string {
    case SHOW = 'Tj';
    case MOVE_SHOW = '\'';
    case MOVE_SHOW_SPACING = '"';
    case SHOW_ARRAY = 'TJ';

    public function getNewTextState(string $operands, ?TextState $textState): ?TextState {
        if ($this === self::MOVE_SHOW_SPACING) {
            $spacing = explode(' ', trim($operands));
            if (count($spacing) !== 2) {
                throw new \RuntimeException();
            }

            return new TextState(
                $textState->fontName,
                $textState->fontSize,
                (float) $spacing[1],
                (float) $spacing[0],
                $textState->scale ?? 100,
                $textState->leading ?? 0,
                $textState->render ?? 0,
                $textState->rise ?? 0,
            );
        }

        return $textState;
    }

    public function getNewTextMatrix(string $operands, TextMatrix $textMatrix): TextMatrix {
        return $textMatrix;
    }
}
