<?php

/**
 * Represents an extension for MediaWiki
 * Created on Jul 20, 2006
 *
 * @author Gregory Szorc <gregory.szorc@gmail.com>
 */
class MediaWikiFarmer_Extension {

	/** @var string */
	private $name;
	/** @var string */
	private $description;
	/** @var int */
	private $id;

	/**
	 * List of files that need to be included for this extension to work
	 * @var string[]
	 */
	private $includeFiles = [];

	/**
	 * @param stdClass $row
	 * @return self
	 */
	public static function newFromRow( $row ) {
		$ext = new self( $row->fe_name, $row->fe_description, $row->fe_path );
		$ext->id = $row->fe_id;
		return $ext;
	}

	/**
	 * @param string $name
	 * @param string $description
	 * @param string $include
	 */
	public function __construct( $name, $description, $include ) {
		$this->name = $name;
		$this->description = $description;
		$this->includeFiles[] = $include;
	}

	/**
	 * Magic method so we can access variables directly without accessor
	 * functions
	 * @param string $key
	 * @return mixed
	 */
	public function __get( $key ) {
		return $this->$key ?? null;
	}

	/**
	 * Sees if extension is valid by looking at included files and attempting to
	 * open them
	 * @return bool
	 */
	public function isValid() {
		foreach ( $this->includeFiles as $file ) {
			$result = fopen( $file, 'r', true );

			if ( $result === false ) {
				return false;
			}

			fclose( $result );
		}

		return true;
	}
}
