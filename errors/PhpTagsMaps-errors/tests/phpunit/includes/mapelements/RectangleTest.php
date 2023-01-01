<?php

namespace MultiMaps;

class RectangleTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @covers MultiMaps\Rectangle::getElementName
	 */
	public function testGetElementName() {
		$this->assertSame(
			'Rectangle',
			( new Rectangle )->getElementName()
		);
	}
}
