<?php
namespace Doubleedesign\Comet\Core;

/**
 * Utility class to handle the rendering of preprocessed HTML content
 * so it can be inserted into a Comet component as an "innerComponent"
 */
class PreprocessedHTML {
	private string $content;

	function __construct(string $content) {
		$this->content = $content;
	}

	public function render(): void {
		echo Utils::sanitise_content($this->content);
	}
}