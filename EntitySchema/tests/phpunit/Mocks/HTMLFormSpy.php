<?php

namespace EntitySchema\Tests\Mocks;

use HTMLForm;
use PHPUnit\Framework\Assert;

/**
 * @license GPL-2.0-or-later
 */
class HTMLFormSpy {

	/** @var HTMLForm */
	private static $form;

	public static function factory( ...$arguments ) {
		self::$form = HTMLForm::factory( ...$arguments );
		return self::$form;
	}

	public static function assertFormFieldData( $expectedContent ) {
		Assert::assertSame( $expectedContent, self::$form->mFieldData );
	}

}
