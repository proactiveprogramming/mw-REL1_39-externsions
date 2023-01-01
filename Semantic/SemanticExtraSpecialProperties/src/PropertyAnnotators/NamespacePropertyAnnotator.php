<?php

namespace SESP\PropertyAnnotators;

use SESP\AppFactory;
use SESP\PropertyAnnotator;
use SMW\DIProperty;
use SMW\SemanticData;
use SMWDINumber;

/**
 * @private
 * @ingroup SESP
 *
 * @license GNU GPL v2+
 */
class NamespacePropertyAnnotator implements PropertyAnnotator {

	/**
	 * Predefined property ID
	 */
	const PROP_ID = '___NSID';

	/**
	 * @var AppFactory
	 */
	private $appFactory;

	/**
	 * @param AppFactory $appFactory
	 */
	public function __construct( AppFactory $appFactory ) {
		$this->appFactory = $appFactory;
	}

	/**
	 * {@inheritDoc}
	 */
	public function isAnnotatorFor( DIProperty $property ) {
		return $property->getKey() === self::PROP_ID;
	}

	/**
	 * {@inheritDoc}
	 */
	public function addAnnotation( DIProperty $property, SemanticData $semanticData ) {
		$dataItem = new SMWDINumber( $semanticData->getSubject()->getTitle()->getNamespace() );
		$semanticData->addPropertyObjectValue( $property, $dataItem );
	}
}
