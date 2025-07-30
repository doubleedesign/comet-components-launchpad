<?php
/** @noinspection PhpMultipleClassDeclarationsInspection */
namespace Doubleedesign\Comet\Core;
use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class AllowedTags {

    /**
     * @param  array<Tag>  $tags
     */
    public function __construct(public array $tags) {}
}
