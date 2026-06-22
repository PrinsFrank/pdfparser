<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Encoding;

use Override;

/** @see Annex D.3 */
class PDFDocEncoding implements Encoding {
    private const CHAR_MAP = [
        128 => "\u{2022}",
        129 => "\u{2020}",
        130 => "\u{2021}",
        131 => "\u{2026}",
        132 => "\u{2014}",
        133 => "\u{2013}",
        134 => "\u{0192}",
        135 => "\u{2044}",
        136 => "\u{2039}",
        137 => "\u{203A}",
        138 => "\u{2212}",
        139 => "\u{2030}",
        140 => "\u{201E}",
        141 => "\u{201C}",
        142 => "\u{201D}",
        143 => "\u{2018}",
        144 => "\u{2019}",
        145 => "\u{201A}",
        146 => "\u{2122}",
        147 => "\u{FB01}",
        148 => "\u{FB02}",
        149 => "\u{0141}",
        150 => "\u{0152}",
        151 => "\u{0160}",
        152 => "\u{0178}",
        153 => "\u{017D}",
        154 => "\u{0131}",
        155 => "\u{0142}",
        156 => "\u{0153}",
        157 => "\u{0161}",
        158 => "\u{017E}",
        159 => "\u{FFFD}",
        160 => "\u{20AC}",
    ];

    #[Override]
    public static function textToUnicode(string $string): string {
        $result = '';
        $length = strlen($string);
        for ($i = 0; $i < $length; $i++) {
            $byte = ord($string[$i]);
            if ($byte < 128) {
                $result .= chr($byte);
            } elseif ($byte >= 161) {
                $result .= mb_convert_encoding(chr($byte), 'UTF-8', 'ISO-8859-1');
            } else {
                $result .= self::CHAR_MAP[$byte];
            }
        }

        return $result;
    }
}
