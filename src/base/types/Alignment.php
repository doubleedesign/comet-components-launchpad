<?php
namespace Doubleedesign\Comet\Core;

enum Alignment: string {
    case START = 'start';
    case END = 'end';
    case CENTER = 'center';
    case JUSTIFY = 'justify';
    case MATCH_PARENT = 'match-parent';

    public static function fromString(string $value): ?self {
        return match ($value) {
            'start', 'left', 'top' => self::START,
            'end', 'right', 'bottom' => self::END,
            'center'  => self::CENTER,
            'justify' => self::JUSTIFY,
            default   => self::MATCH_PARENT
        };
    }

    public function isDefault(): bool {
        return $this === self::MATCH_PARENT || $this === self::START;
    }
}
