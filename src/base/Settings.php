<?php
namespace Doubleedesign\Comet\Core;

class Settings {
    /**
     * @var array<Tag>
     */
    public const INLINE_PHRASING_ELEMENTS = [Tag::SPAN, Tag::EM, Tag::STRONG, Tag::A, Tag::I, Tag::MARK, Tag::CITE, Tag::S, Tag::SUP, Tag::SUB, Tag::BUTTON, Tag::IMG, Tag::WBR, Tag::BR, Tag::TIME, Tag::CODE, Tag::KBD, Tag::BDI, Tag::BDO];

    /**
     * @var array<Tag>
     */
    public const BLOCK_PHRASING_ELEMENTS = [Tag::H1, Tag::H2, Tag::H3, Tag::H4, Tag::H5, Tag::H6, Tag::P, Tag::LI, Tag::BUTTON, Tag::SUMMARY];

}
