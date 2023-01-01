<?php

namespace SESP\Tests\PropertyAnnotators;

use SESP\PropertyAnnotators\NamespacePropertyAnnotator;
use SMW\DIProperty;
use SMW\DIWikiPage;
use SMWDINumber;


/**
 * @covers \SESP\PropertyAnnotators\NamespacePropertyAnnotator
 * @group semantic-extra-special-properties
 *
 * @license GNU GPL v2+
 */
class NamespacePropertyAnnotatorTest extends \PHPUnit_Framework_TestCase {

	private $property;
	private $appFactory;

	protected function setUp(): void {
		parent::setUp();

		$this->appFactory = $this->getMockBuilder( '\SESP\AppFactory' )
			->disableOriginalConstructor()
			->getMock();

		$this->property = new DIProperty( '___NSID' );
	}

	/**
	 * @covers \SESP\PropertyAnnotators\NamespacePropertyAnnotator
	 */
	public function testCanConstruct() {

		$this->assertInstanceOf(
			NamespacePropertyAnnotator::class,
			new NamespacePropertyAnnotator( $this->appFactory )
		);
	}

	/**
	 * @covers \SESP\PropertyAnnotators\NamespacePropertyAnnotator::isAnnotatorFor
	 */
	public function testIsAnnotatorFor() {

		$annotator = new NamespacePropertyAnnotator(
			$this->appFactory
		);

		$this->assertTrue(
			$annotator->isAnnotatorFor( $this->property )
		);
	}

	/**
	 * @covers \SESP\PropertyAnnotators\NamespacePropertyAnnotator::addAnnotation
	 */
	public function testAddAnnotation() {
		$namespace = NS_USER;
		$subject = DIWikiPage::newFromText( __METHOD__, $namespace );

		$semanticData = $this->getMockBuilder( '\SMW\SemanticData' )
			->disableOriginalConstructor()
			->getMock();

		$semanticData->expects( $this->once() )
			->method( 'getSubject' )
			->will( $this->returnValue( $subject ) );

		$semanticData->expects( $this->once() )
			->method( 'addPropertyObjectValue' )
			->with(
				$this->equalTo( $this->property ),
				$this->equalTo( new SMWDINumber( $namespace ) ) );
		$annotator = new NamespacePropertyAnnotator(
			$this->appFactory
		);

		$annotator->addAnnotation( $this->property, $semanticData );
	}
}
