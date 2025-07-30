<?php
namespace Doubleedesign\Comet\Core;

#[AllowedTags([Tag::DIV, Tag::SECTION, Tag::HEADER, Tag::FOOTER, Tag::MAIN, Tag::ARTICLE, Tag::ASIDE])]
#[DefaultTag(Tag::DIV)]
abstract class UIComponent extends Renderable {
    /**
     * @var array<Renderable> $innerComponents
     * @description Inner components to be rendered within this component
     */
    protected array $innerComponents;

    /**
     * UIComponent constructor
     *
     * @param  array<string, string|int|array|null>  $attributes
     * @param  array<Renderable>  $innerComponents
     * @param  string  $bladeFile
     */
    public function __construct(array $attributes, array $innerComponents, string $bladeFile) {
        parent::__construct($attributes, $bladeFile);
        $this->innerComponents = $innerComponents;
    }

    protected function get_filtered_classes(): array {
        $classes = parent::get_filtered_classes();
        
        // Transform WordPress class names
        return array_map(function($class) {
            return str_replace('is-style-', "{$this->get_bem_name()}--", $class);
        }, $classes);
    }

    /**
     * Get the filtered class list for this component as a string
     *
     * @return string
     */
    protected function get_filtered_classes_string(): string {
        return implode(' ', $this->get_filtered_classes());
    }

}
