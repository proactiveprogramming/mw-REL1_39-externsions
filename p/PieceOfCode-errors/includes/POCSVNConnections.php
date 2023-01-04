<?php
/**
 * @file POCSVNConnections.php
 *
 * Subversion
 *	- ID:  $Id$
 *	- URL: $URL$
 *
 * @copyright 2010 Alejandro Darío Simi
 * @license GPL
 * @author Alejandro Darío Simi
 * @date 2010-08-28
 */

class POCSVNConnections {
	/**
	 * @var POCSVNConnections
	 */
	private static	$_Instance;

	/**
	 * @var array
	 */
	protected	$_connections;
	/**
	 * @var POCErrorsHolder
	 */
	protected	$_errors;
	/**
	 * @var bool
	 */
	protected	$_isLoaded;

	protected function __construct() {
		$this->_errors = POCErrorsHolder::Instance();

		$this->_connections = array();
		$this->_isLoaded    = false;

		$this->load();
	}
	/**
	 * Prevent users to clone the instance.
	 */
	public function __clone() {
		trigger_error(__CLASS__.': Clone is not allowed.', E_USER_ERROR);
	}

	/*
	 * Public methods.
	 */
	/**
	 * @todo doc
	 */
	public function getConnection($connection) {
		return (isset($this->_connections[$connection]) ? $this->_connections[$connection] : false);
	}
	/**
	 * @todo doc
	 */
	public function isLoaded() {
		return $this->_isLoaded;
	}

	/*
	 * Protected Methods
	 */
	/**
	 * @todo doc
	 */
	protected function load() {
		global	$wgPieceOfCodeSVNConnections;

		foreach($wgPieceOfCodeSVNConnections as $k => $v) {
			if(is_array($v)) {
				if(isset($v['url'])) {
					$this->_connections[$k] = $v;
				}
				if(!isset($this->_connections[$k]['username']) || !isset($this->_connections[$k]['password'])) {
					$this->_connections[$k]['username'] = false;
					$this->_connections[$k]['password'] = false;
				}
			}
		}
		$this->_isLoaded = true;

		return $this->isLoaded();
	}

	/*
	 * Public class methods
	 */
	/**
	 * @return Returns the singleton instance of this class POCSVNConnections.
	 */
	public static function Instance() {
		if (!isset(self::$_Instance)) {
			$c = __CLASS__;
			self::$_Instance = new $c;
		}

		return self::$_Instance;
	}
}

?>
