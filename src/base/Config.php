<?php
namespace Doubleedesign\Comet\Core;

class Config {
    private static ThemeColor $globalBackground = ThemeColor::WHITE;
    private static string $iconPrefix = 'fa-solid';
    private static array $componentPaths = [];

    public static function set_global_background(string $color): void {
        self::$globalBackground = ThemeColor::tryFrom($color);
    }

    public static function get_global_background(): string {
        return self::$globalBackground->value;
    }

    public static function set_icon_prefix(string $prefix): void {
        self::$iconPrefix = $prefix;
    }

    public static function get_icon_prefix(): string {
        return self::$iconPrefix;
    }

    public static function set_blade_component_paths(array $paths): void {
        // TODO: Add some validation here
        self::$componentPaths = $paths;
    }

    public static function get_blade_component_paths(): array {
        return self::$componentPaths ?? [];
    }
}
