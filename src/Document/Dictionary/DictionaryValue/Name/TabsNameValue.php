<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Name;

enum TabsNameValue: string implements NameValue {
    case RowOrder = 'R';
    case ColumnOrder = 'C';
    case StructureOrder = 'S';
}