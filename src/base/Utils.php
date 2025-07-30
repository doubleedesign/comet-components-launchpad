<?php
namespace Doubleedesign\Comet\Core;
use HTMLPurifier;
use HTMLPurifier_Config;
use Traversable;

class Utils {

    /**
     * If a string value has spaces, convert it to kebab case
     *
     * @param  string  $value
     *
     * @return string
     */
    public static function kebab_case(string $value): string {
        // Account for PascalCase
        $value = preg_replace('/([a-z])([A-Z])/', '$1 $2', $value);

        // Convert slashes so this works on namespaced block names
        $value = str_replace('/', '-', $value);

        // Also replace double underscores (used for BEM naming manipulations)
        $value = str_replace('__', '-', $value);

        // Convert whitespace to hyphens and make lowercase
        $value = trim(strtolower(preg_replace('/\s+/', '-', $value)));

        // Trim any leading or trailing hyphens
        return trim($value, '-');
    }

    /**
     * Convert string value to PascalCase
     *
     * @param  string  $value
     *
     * @return string
     */
    public static function pascal_case(string $value): string {
        $value = str_replace(['-', '_'], ' ', $value);

        return str_replace(' ', '', ucwords($value));
    }

    /**
     * Sanitise content string using HTMLPurifier
     *
     * @param  string  $content  The input content to be sanitised
     * @param  ?array<Tag>  $allowedTags
     *
     * @return string The sanitised content.
     */
    public static function sanitise_content(string $content, ?array $allowedTags = null): string {
        $config = HTMLPurifier_Config::createDefault();
        $purifier = new HTMLPurifier($config);

        if (!$allowedTags) {
            return $purifier->purify($content);
        }

        // HTML Purifier does have an option to pass allowed tags to it,
        // but in that case we'd also have to pass all their attributes, which may be overkill
        $allowedTagsAsTags = array_map(fn($tag) => "<$tag->value>", $allowedTags);
        $updatedContent = strip_tags($content, $allowedTagsAsTags);

        return $purifier->purify($updatedContent);
    }

    /**
     * Convert lowercase, kebab-case, and WP block format component names to PascalCase,
     * account for components with reserved words as names having "Component" appended (e.g., ListComponent),
     * account for some other edge cases where I really really want to rename a component,
     * and add the namespace to return the full class name
     *
     * @param  string  $name
     *
     * @return string
     */
    public static function get_class_name(string $name): string {
        $reserved_words = ['List'];

        $shortName = array_reverse(explode('/', $name))[0];
        $className = Utils::pascal_case($shortName);
        if (in_array($className, $reserved_words)) {
            $className = $className . 'Component';
        }

        if ($shortName === 'buttons') {
            $className = 'ButtonGroup';
        }

        if ($shortName === 'image-and-text-image-wrapper') {
            $className = 'ImageWrapper';
        }
        if ($shortName === 'image-and-text-content') {
            $className = 'ContentWrapper';
        }

        return sprintf('%s\\%s', __NAMESPACE__, $className);
    }

    /**
     * Ensure an indexed array of objects is flat
     *
     * @param  array  $array
     *
     * @return array
     */
    public static function array_flat(array $array): array {
        // If the array is empty, bail early
        if (empty($array)) {
            return [];
        }
        
        // This is an array of arrays and needs to be flattened
        if (is_array($array[0])) {
            return array_merge(...$array);
        }

        return $array;
    }

    /**
     * Deep merging of a multidimensional array where one is a partial variation of the other
     *
     * @param  array  $original
     * @param  array  $partial
     *
     * @return array
     */
    public static function array_merge_deep(array $original, array $partial): array {
        $result = $original;

        foreach ($partial as $key => $value) {
            // If both are arrays but the original is indexed (numeric keys),
            // or if the value isn't an array, replace entirely
            if (!isset($original[$key]) || !is_array($value) || (is_array($original[$key]) && array_is_list($original[$key]))) {
                $result[$key] = $value;
            }
            // If both are associative arrays, merge recursively
            else if (is_array($original[$key])) {
                $result[$key] = self::array_merge_deep($original[$key], $value);
            }
        }

        return $result;
    }

    public static function get_array_depth($array) {
        $max_depth = 1;

        foreach ($array as $value) {
            if (is_array($value)) {
                $depth = self::get_array_depth($value) + 1;
                $max_depth = max($max_depth, $depth);
            }
        }

        return $max_depth;
    }

    public static function sort_associative_array_with_given_key_order(array $array, array $keyOrder): array {
        $result = [];

        // First, add elements in the specified order
        foreach ($keyOrder as $key) {
            if (array_key_exists($key, $array)) {
                $result[$key] = $array[$key];
                // Remove this key from the original array
                unset($array[$key]);
            }
        }

        // Then add any remaining keys
        foreach ($array as $key => $value) {
            $result[$key] = $value;
        }

        return $result;
    }

    public static function get_first_phrase_from_html_string(string $content): string {
        // Remove any leading/trailing whitespace
        $trimmed = trim($content);

        // Try splitting by common HTML elements
        $blockParts = preg_split('/<\/?(?:span|strong|em|div|p|br|h[1-6])[^>]*>/i', $trimmed);

        // Get first non-empty block
        foreach ($blockParts as $block) {
            $stripped = trim(strip_tags($block));
            if ($stripped !== '') {
                return $stripped;
            }
        }

        // If no content found, return the original string
        return $trimmed;
    }

    /**
     * Extract specified properties from an object or array of objects
     *
     * @param  mixed  $target  Single object, array of objects, or Traversable
     * @param  array  $props  Array of property names to extract
     * @param  bool  $preserveKeys  Whether to preserve array keys in the result
     *
     * @return array Associative array with extracted properties
     */
    public static function pick_object_properties(mixed $target, array $props, bool $preserveKeys = true): array {
        $result = [];

        // Handle single object case
        if (is_object($target) && !($target instanceof Traversable)) {
            $singleResult = [];
            foreach ($props as $prop) {
                if (property_exists($target, $prop)) {
                    $singleResult[$prop] = $target->$prop;
                }
            }

            return $singleResult;
        }

        // Handle array or Traversable
        foreach ($target as $key => $item) {
            $itemResult = [];
            if (is_object($item)) {
                foreach ($props as $prop) {
                    if (property_exists($item, $prop)) {
                        $itemResult[$prop] = $item->$prop;
                    }
                }
            }
            elseif (is_array($item)) {
                foreach ($props as $prop) {
                    if (array_key_exists($prop, $item)) {
                        $itemResult[$prop] = $item[$prop];
                    }
                }
            }

            if ($preserveKeys) {
                $result[$key] = $itemResult;
            }
            else {
                $result[] = $itemResult;
            }
        }

        return $result;
    }
}
