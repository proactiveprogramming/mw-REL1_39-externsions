<?php

namespace MediaWiki\Extension\ImportOfficeFiles\Tests;

use MediaWiki\Extension\ImportOfficeFiles\FilenameBuilder;
use PHPUnit\Framework\TestCase;

/**
 * @covers \MediaWiki\Extension\ImportOfficeFiles\FilenameBuilder
 */
class FilenameBuilderTest extends TestCase {
/**
 * @covers \MediaWiki\Extension\ImportOfficeFiles\FilenameBuilder::build
 */
	public function testBuild() {
		$builder = new FilenameBuilder();

		$actual = $builder->build(
			'TestCase',
			'Some base title',
			'image1.jpg'
		);
		$expected = "TestCase_Some_base_title_image1.jpg";
		$this->assertEquals( $expected, $actual );

		$actual = $builder->build(
			'TestCase',
			'Some base title',
			'image2.jpg',
			true
		);
		$expected = "TestCase:Some_base_title_image2.jpg";
		$this->assertEquals( $expected, $actual );

		$actual = $builder->build(
			'TestCase',
			'Some base title/Some sub page',
			'image3.jpg',
			true
		);
		$expected = "TestCase:Some_base_title_Some_sub_page_image3.jpg";
		$this->assertEquals( $expected, $actual );

		$actual = $builder->build(
			'TestCase',
			'TestCase:Some base title',
			'image1.jpg'
		);
		$expected = "TestCase_Some_base_title_image1.jpg";
		$this->assertEquals( $expected, $actual );

		$actual = $builder->build(
			'TestCase',
			'TestCase_Some base title',
			'image1.jpg'
		);
		$expected = "TestCase_Some_base_title_image1.jpg";
		$this->assertEquals( $expected, $actual );
	}
}
