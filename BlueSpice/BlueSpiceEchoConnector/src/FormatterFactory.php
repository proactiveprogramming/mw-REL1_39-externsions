<?php

namespace BlueSpice\EchoConnector;

use Wikimedia\ObjectFactory\ObjectFactory;

class FormatterFactory {
	/** @var ObjectFactory */
	private $objectFactory;
	/** @var array */
	private $specs;
	/** @var array */
	private $instances = [];

	/**
	 * @param ObjectFactory $objectFactory
	 * @param array $specs
	 */
	public function __construct( ObjectFactory $objectFactory, array $specs ) {
		$this->specs = $specs;
		$this->objectFactory = $objectFactory;
	}

	/**
	 * @param string $format
	 * @param bool $noCache
	 * @param array $args
	 * @return mixed
	 * @throws \Exception
	 */
	public function getForFormat( $format, $noCache = false, $args = [] ) {
		if ( !isset( $this->specs[$format] ) ) {
			throw new \Exception( 'No formatter registered for format ' . $format );
		}
		if ( !isset( $this->instances[$format] ) || $noCache ) {
			$spec = $this->specs[$format];
			$origArgs = $spec['args'] ?? [];
			$args = array_merge( $origArgs, $args );
			$spec['args'] = $args;
			$object = $this->objectFactory->createObject( $spec );
			if ( !$object ) {
				throw new \Exception( 'Cannot create formatter for ' . $format );
			}
			$this->instances[$format] = $object;
		}

		return $this->instances[$format];
	}
}
