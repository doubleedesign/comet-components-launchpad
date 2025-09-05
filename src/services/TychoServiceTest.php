<?php
/** @noinspection PhpUnhandledExceptionInspection */

namespace Doubleedesign\Comet\Core;
use ReflectionClass;
use DOMDocument;

describe('TychoService', function() {

    describe('Extract attributes from XML node', function() {

        test('Boolean attribute value', function() {
            $instance = new TychoService();
            $node = <<<TYCHO
			<Container withWrapper="false"></Container>
			TYCHO;
            $dom = new DOMDocument();
            $dom->loadXML($node);
            $node = $dom->documentElement;

            // Access private method using Reflection
            $reflector = new ReflectionClass(TychoService::class);
            $method = $reflector->getMethod('extract_attributes');

            $result = $method->invoke($instance, $node);
            expect($result)->toBe(['withWrapper' => false]);
        });

        test('Integer attribute value', function() {
            $instance = new TychoService();
            $node = <<<TYCHO
			<Steps maxPerRow="2"></Steps>
			TYCHO;
            $dom = new DOMDocument();
            $dom->loadXML($node);
            $node = $dom->documentElement;

            // Access private method using Reflection
            $reflector = new ReflectionClass(TychoService::class);
            $method = $reflector->getMethod('extract_attributes');

            $result = $method->invoke($instance, $node);
            expect($result)->toBe(['maxPerRow' => 2]);
        });

        test('Classes attribute - space-separated string to array', function() {
            $instance = new TychoService();
            $node = <<<TYCHO
			<Heading classes="class1 class2">
				Hello world
			</Heading>
			TYCHO;
            $dom = new DOMDocument();
            $dom->loadXML($node);
            $node = $dom->documentElement;

            // Access private method using Reflection
            $reflector = new ReflectionClass(TychoService::class);
            $method = $reflector->getMethod('extract_attributes');

            $result = $method->invoke($instance, $node);
            expect($result)->toBe(['classes' => ['class1', 'class2']]);
        });

    });
});
