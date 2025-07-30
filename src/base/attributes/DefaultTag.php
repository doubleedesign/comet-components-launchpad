<?php
/** @noinspection PhpMultipleClassDeclarationsInspection */
namespace Doubleedesign\Comet\Core;
use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class DefaultTag {

    public function __construct(public Tag $tag) {}
}
