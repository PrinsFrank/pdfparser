<?php
declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Text\OperatorString;

use PrinsFrank\PdfParser\Document\Object\Decorator\Font;
use PrinsFrank\PdfParser\Exception\ParseFailureException;

enum TextShowingOperator: string {
    case SHOW = 'Tj';
    case MOVE_SHOW = '\'';
    case MOVE_SHOW_SPACING = '"';
    case SHOW_ARRAY = 'TJ';

    public function displayOperands(string $operands, ?Font $font): string {
        $regex = match ($this) {
            self::SHOW_ARRAY => '/(?<chars>(<[^>]+>)|(\([^)]+\)))(?<offset>-?[0-9]+(\.[0-9]+)?)?/',
            self::SHOW => '/^(?<chars>(<[^>]+>)|(\([^)]+\)))$/',
            default => throw new ParseFailureException($this->name . ':' . $operands),
        };

        if (preg_match_all($regex, $operands, $matches, PREG_SET_ORDER) === 0) {
            throw new ParseFailureException('"' . $operands . '"');
        }

        $string = '';
        foreach ($matches as $match) {
            if (str_starts_with($match['chars'], '(') && str_ends_with($match['chars'], ')')) {
                $string .= substr($match['chars'], 1, -1);
            } elseif (str_starts_with($match['chars'], '<') && str_ends_with($match['chars'], '>')) {
                if ($font === null) {
                    throw new ParseFailureException('No font available');
                }

                $string .= $font->toUnicode(substr($match['chars'], 1, -1));
            } else {
                throw new ParseFailureException(sprintf('Unrecognized character group format "%s"', $match['chars']));
            }

            if ((int) ($match['offset'] ?? 0) < -20) {
                $string .= ' ';
            }
        }

        return $string;
    }
}
