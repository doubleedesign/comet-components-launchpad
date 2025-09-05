<?php
namespace Doubleedesign\Comet\Core;

enum ContainerSize: string {

    // These should match the container breakpoints used in the CSS
    // and don't necessarily align to the actively expected/supported widths of all elements that use this (though we probably should support them all)
    case WIDE = 'wide';
    case FULLWIDTH = 'fullwidth';
    case NARROW = 'narrow';
    case NARROWER = 'narrower';
    case SMALL = 'small';
    case DEFAULT = 'default';

    public static function from_wordpress_class_name(string $value): ?self {
        return match ($value) {
            // These are the sizes we expect the Container component to have
            'is-style-wide'      => self::WIDE,
            'is-style-fullwidth' => self::FULLWIDTH,
            'is-style-narrow'    => self::NARROW,
            default              => self::DEFAULT
        };
    }
}
