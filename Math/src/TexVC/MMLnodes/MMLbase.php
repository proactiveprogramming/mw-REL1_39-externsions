<?php

namespace MediaWiki\Extension\Math\TexVC\MMLnodes;

use MediaWiki\Extension\Math\TexVC\MMLmappings\TexConstants\Tag;

class MMLbase {
	private string $name;
	private string $texclass;
	private array $attributes;

	public function __construct( string $name, string $texclass = "", array $attributes = [] ) {
		$this->name = $name;
		$this->texclass = $texclass;
		$this->attributes = $attributes;
	}

	public function name(): string {
		return $this->name;
	}

	/**
	 * Encapsulating the input with start and end element
	 *
	 * @param string $input input content
	 * @return string <start> input <end>
	 */
	public function encapsulate( $input ): string {
		return $this->getStart() .
			$input .
			$this->getEnd();
	}

	/**
	 * Getting the start element
	 * @return string
	 */
	public function getStart(): string {
		$start = $this->getStartWithAttributes();
		$start .= ">";
		return $start;
	}

	/**
	 * Gets an empty element with the specified name.
	 * Example: "<mrow/>"
	 * @return string
	 */
	public function getEmpty(): string {
		return $this->getStartWithAttributes() . "/>";
	}

	/**
	 * Getting the end element
	 * @return string
	 */
	public function getEnd(): string {
		return "</" . $this->name() . ">";
	}

	/**
	 * Gets the starting tag with attributes
	 * @return string
	 */
	private function getStartWithAttributes(): string {
		$start = "<" . $this->name();
		if ( $this->texclass !== "" ) {
			$start .= " " . Tag::CLASSTAG . "=\"" . $this->texclass . "\"";
		}
		foreach ( $this->attributes as $key => $value ) {
			$start .= " " . $key . "=" . "\"" . $value . "\"";
		}
		return $start;
	}
}
