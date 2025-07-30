<?php
namespace Doubleedesign\Comet\Core;
use ReflectionClass;

abstract class Renderable {
    /**
     * @var array<string, string|int|array|null> $rawAttributes
     * @description Raw attributes passed to the component constructor as key-value pairs
     */
    private array $rawAttributes;

    /**
     * @var ?Tag
     * @description The HTML tag to use for this component
     */
    protected ?Tag $tagName = Tag::DIV;

    /**
     * @var string|null $id
     * @description Unique identifier
     */
    protected ?string $id;

    /**
     * @var array<string> $classes
     * @description CSS classes
     */
    protected ?array $classes = [];

    /**
     * @var array|null $style
     * @description Inline styles
     */
    protected ?array $style = null;

    /**
     * @var ?string $context
     * @description The kebab-case or BEM name of the parent component or variant if contextually relevant. May be automatically set by parent component(s).
     */
    protected ?string $context = null;

    /**
     * The dot-delimited path to the Blade template file
     *
     * @var string
     */
    protected string $bladeFile;

    /**
     * @var string $shortName
     * @description The name of the component without any namespacing, prefixes, etc. Derived from the Blade filename by default.
     */
    protected string $shortName;

    /**
     * @var string|null $testId
     * @description Optionally add a data-testid attribute for automated tests
     */
    protected ?string $testId;

    public function __construct(array $attributes, string $bladeFile) {
        $this->rawAttributes = $attributes;
        $this->set_tag($attributes['tagName'] ?? null);
        $this->id = isset($attributes['id']) ? Utils::kebab_case($attributes['id']) : null;
        $this->style = (isset($attributes['style']) && is_array($attributes['style'])) ? $attributes['style'] : null;
        $this->context = $attributes['context'] ?? $this->context;
        $this->bladeFile = $bladeFile;
        $this->shortName = array_reverse(explode('.', $this->bladeFile))[0];
        $this->testId = $attributes['testId'] ?? null;

        // If we are in WordPress, allow overriding Blade template from the theme
        if (class_exists('WP_Block') && function_exists('get_template_directory') && function_exists('get_stylesheet_directory')) {
            $themeBladeFile = get_stylesheet_directory() . "/components/{$this->shortName}.blade.php";
            if (file_exists($themeBladeFile)) {
                $this->bladeFile = "components.{$this->shortName}";
            }
            else {
                $parentThemeBladeFile = get_template_directory() . "/components/{$this->shortName}.blade.php";
                if (file_exists($parentThemeBladeFile)) {
                    $this->bladeFile = str_replace('/', '\\', $parentThemeBladeFile);
                }
            }
        }

        $classes = [];
        // Handle WordPress block implementation of classes (className string)
        if (isset($attributes['className']) && is_string($attributes['className'])) {
            $classes = explode(' ', $attributes['className']);
        }
        // Handle preferred implementation of classes (array)
        if (isset($attributes['classes']) && is_array($attributes['classes'])) {
            $classes = array_merge($classes, $attributes['classes']);
        }
        $this->classes = $classes;

        // If a CSS and/or JS file is in the directory, add it/them to the asset loader if it's available
        if (class_exists('Doubleedesign\Comet\Core\Assets')) {
            // TODO: Make this opt-in somehow, so it's not being run when not used (e.g., in WordPress where the bundles are used)
            // TODO: Also make this work with custom Blade file paths
            $componentRootDir = dirname(__DIR__, 2);
            $thisComponentPathFromBladeFile = str_replace('.', '/', $this->bladeFile);
            $cssFile = $componentRootDir . '/' . $thisComponentPathFromBladeFile . '.css';
            $cssFile = str_replace('\\', '/', $cssFile);
            $jsFile = $componentRootDir . '/' . $thisComponentPathFromBladeFile . '.js';
            $jsFile = str_replace('\\', '/', $jsFile);
            if (file_exists($cssFile)) {
                Assets::get_instance()->add_stylesheet($cssFile);
            }
            if (file_exists($jsFile)) {
                Assets::get_instance()->add_script($jsFile, ['type' => 'module']);
            }
        }
    }

    protected function set_tag(?string $tagName): void {
        $reflection = new ReflectionClass($this);

        // Get allowed tags and default tag from attributes
        $allowedTagsAttr = $reflection->getAttributes(AllowedTags::class)[0] ?? null;
        $defaultTagAttr = $reflection->getAttributes(DefaultTag::class)[0] ?? null;

        $allowedTags = $allowedTagsAttr ? $allowedTagsAttr->newInstance()->tags : null;
        $defaultTag = $defaultTagAttr ? $defaultTagAttr->newInstance()->tag : Tag::DIV;

        // Try to use provided tag, fall back to default
        $requestedTag = $tagName ? Tag::tryFrom($tagName) : $defaultTag;

        // Validate against allowed tags if specified
        if ($allowedTags && !in_array($requestedTag, $allowedTags)) {
            error_log(sprintf(
                'Tag %s is not allowed for %s. Allowed tags: %s. Defaulting to %s.',
                $requestedTag->value,
                static::class,
                implode(', ', array_map(fn($tag) => $tag->value, $allowedTags)),
                $defaultTag->value
            ));

            $requestedTag = $defaultTag;
        }

        $this->tagName = $requestedTag;
    }

    protected function set_context(string $context): void {
        $this->context = $context;
    }

    public function get_id(): ?string {
        return $this->id;
    }

    public function set_id(string $id): void {
        $this->id = $id;
    }

    protected function get_bem_name(): ?string {
        if ($this->context) {
            $kebabContext = Utils::kebab_case($this->context);
            $shortNameToUse = $this->shortName;
            if (str_starts_with($this->shortName, $kebabContext)) {
                $shortNameToUse = str_replace("$kebabContext-", '', $this->shortName);
            }

            return $this->context . '__' . $shortNameToUse;
        }

        return $this->shortName;
    }

    /**
     * Get the valid/supported classes for this component
     * Note: Supported classes noted in docblocks refer to those that have been accounted for in CSS.
     *       They are not the only valid classes - implementations can add their own.
     * Note 2: No sanitisation is done here because using htmlspecialchars() + Blade template output resulted in double encoding.
     *        So let's just let Blade look after it.
     *
     * @return array<string>
     */
    protected function get_filtered_classes(): array {
        $current_classes = $this->classes;
        $redundant_classes = [
            'is-style-default',
            // unwanted WordPress classes that are handled in other ways
            'is-stacked-on-mobile',
            'is-not-stacked-on-mobile'
        ];

        $result = array_merge(
            [$this->get_bem_name()],
            array_filter($current_classes, function($class) use ($redundant_classes) {
                return !in_array($class, $redundant_classes) && !str_starts_with($class, 'wp-elements-');
            })
        );

        return array_unique($result);
    }

    /**
     * Get the valid/supported HTML attributes for the given tag
     *
     * @return array<string>
     */
    private function get_valid_html_attributes(): array {
        return $this->tagName->get_valid_attributes();
    }

    /**
     * Filter the attributes for later use
     *
     * @return array<string, string|int|array|null>
     */
    private function get_filtered_attributes(): array {
        $class_properties = array_keys(get_class_vars(self::class));

        // Filter out:
        // 1. attributes that are handled as separate properties
        // 2. nested arrays such as layout and focalPoint (which should be handled elsewhere)
        // 3. attributes that are not valid/supported HTML attributes for the given tag
        // Explicitly keep:
        // 1. attributes that start with 'data-' (custom data attributes)
        return array_filter($this->rawAttributes, function($key) use ($class_properties) {
            return
                // Stuff to filter out
                $key !== 'class' && $key !== 'style' && !in_array($key, $class_properties) && !is_array($this->rawAttributes[$key]) &&
                // Other stuff to keep - valid attributes for this tag
                in_array($key, $this->get_valid_html_attributes()) ||
                str_starts_with($key, 'data-');
        }, ARRAY_FILTER_USE_KEY);
    }

    /**
     * Collect the final HTML attributes excluding "class"
     *
     * @return array<string,string>
     */
    protected function get_html_attributes(): array {
        $baseAttributes = $this->get_filtered_attributes();
        $styles = $this->get_inline_styles() ?? [];

        $attrs = array_merge(
            $baseAttributes,
            array(
                'id'    => $this->get_id(),
                'style' => implode(';',
                    array_map(function($key, $value) {
                        if (is_array($value)) {
                            return null;
                        } // Skip WordPress's style arrays

                        return $key . ':' . $value;
                    },
                        array_keys($styles),
                        array_values($styles),
                    )
                )
            )
        );

        if ($this->testId) {
            $attrs['data-testid'] = $this->testId;
        }

        // Remove any empty attributes before returning
        return array_filter($attrs, fn($value) => !empty($value));
    }

    /**
     * Build the inline styles (style attribute) as a single string
     * using the relevant supported attributes
     *
     * @return array<string, string>
     */
    protected function get_inline_styles(): array {
        return $this->style ?? [];
    }

    abstract public function render(): void;
}
