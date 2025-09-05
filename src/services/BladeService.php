<?php
namespace Doubleedesign\Comet\Core;
use Illuminate\View\{Factory as ViewFactory, FileViewFinder};
use Illuminate\View\Engines\{CompilerEngine, EngineResolver};
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\{Filesystem\Filesystem, Events\Dispatcher};
use RuntimeException, InvalidArgumentException;

class BladeService {
    private static ?ViewFactory $blade = null;
    private static ?BladeCompiler $compiler = null;
    private const CACHE_DIR = '/cache/blade';
    private const TEMPLATE_DIR = DIRECTORY_SEPARATOR;

    public static function getInstance(): ViewFactory {
        if (self::$blade === null) {
            self::initialize();
        }

        return self::$blade;
    }

    /**
     * Set up the Blade templating service by creating the compiler, resolver, and view finder,
     * and registering custom directives
     *
     * @return void
     */
    private static function initialize(): void {
        $filesystem = new Filesystem();
        self::$compiler = self::createCompiler($filesystem);

        $resolver = self::createEngineResolver();
        $viewFinder = self::createViewFinder($filesystem);

        self::$blade = new ViewFactory(
            $resolver,
            $viewFinder,
            new Dispatcher()
        );

        self::registerDirectives();
    }

    /**
     * Create the Blade compiler
     *
     * @param  Filesystem  $filesystem
     *
     * @return BladeCompiler
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    private static function createCompiler(Filesystem $filesystem): BladeCompiler {
        $cachePath = self::setupCacheDirectory();

        return new BladeCompiler($filesystem, $cachePath);
    }

    /**
     * Create the cache directory for Blade templates
     *
     * @throws RuntimeException
     */
    private static function setupCacheDirectory(): string {
        $cachePath = dirname(__DIR__, 2) . self::CACHE_DIR;

        if (!is_dir($cachePath) && !mkdir($cachePath, 0755, true)) {
            throw new RuntimeException("Failed to create cache directory: $cachePath");
        }

        if (!is_writable($cachePath)) {
            throw new RuntimeException("Cache directory is not writable: $cachePath");
        }

        return $cachePath;
    }

    /**
     * Create the Blade engine resolver
     *
     * @return EngineResolver
     */
    private static function createEngineResolver(): EngineResolver {
        $resolver = new EngineResolver();
        $resolver->register('blade', function() {
            return new CompilerEngine(self::$compiler);
        });

        return $resolver;
    }

    /**
     * Create the Blade view finder
     *
     * @param  Filesystem  $filesystem
     *
     * @return FileViewFinder
     * @throws RuntimeException
     */
    private static function createViewFinder(Filesystem $filesystem): FileViewFinder {
        $templatePath = dirname(__DIR__, 1) . self::TEMPLATE_DIR;
        if (!is_dir($templatePath)) {
            throw new RuntimeException("Template directory not found: $templatePath");
        }

        // Allow for directory paths to be set in the config
        $componentPaths = Config::get_blade_component_paths();

        // If we are in WordPress with the block editor, allow overriding from the theme
        if (class_exists('WP_Block')) {
            $wpThemeOverridePath = get_stylesheet_directory() . DIRECTORY_SEPARATOR;
            $wpParentThemeOverridePath = get_template_directory() . DIRECTORY_SEPARATOR;

            return new FileViewFinder($filesystem, [$wpThemeOverridePath, $wpParentThemeOverridePath, $templatePath, ...$componentPaths]);
        }

        return new FileViewFinder($filesystem, [$templatePath, ...$componentPaths]);
    }

    /**
     * Register custom Blade template directives
     *
     * @return void
     * @throws InvalidArgumentException
     */
    private static function registerDirectives(): void {
        self::$compiler->directive('attributes', self::getAttributesDirective());
    }

    /**
     * Content of the custom attributes directive
     *
     * @return callable
     */
    private static function getAttributesDirective(): callable {
        return function($expression) {
            $expression = trim($expression, '()'); // Remove any parentheses Blade wraps around our expression

            return sprintf("<?php foreach(%s as \$key => \$value) { 
               if(!empty(\$value)) echo ' ' . \$key . '=\"' . \$value . '\"';
           } ?>", $expression);
        };
    }
}
