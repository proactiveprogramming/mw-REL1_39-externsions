<?php
/**
 * @file POCErrorsHolder.php
 *
 * Subversion
 *	- ID:  $Id$
 *	- URL: $URL$
 *
 * @copyright 2010 Alejandro Darío Simi
 * @license GPL
 * @author Alejandro Darío Simi
 * @date 2010-08-30
 */

class POCErrorsHolder {
	/**
	 * @var POCErrorsHolder
	 */
	private static	$_Instance;

	/**
	 * Error messages prefix.
	 * @var string
	 */
	protected	$ERROR_PREFIX = "DR_PieceOfCode Error: ";
	/**
	 * @var boolean
	 */
	protected	$_ok;
	/**
	 * @var string
	 */
	protected	$_lastError;

	protected function __construct() {
		$this->clearError();
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
	 * @return @todo doc
	 */
	public function clearError() {
		$this->_lastError = "";
		$this->_ok        = true;
		return $this->getLastError();
	}
	/**
	 * @todo doc
	 * @param $message @todo doc
	 * @return @todo doc
	 */
	public function formatErrorMessage($message) {
		return "<span style=\"color:red;font-weight:bold;\">".$this->ERROR_PREFIX."$message</span>";
	}
	/**
	 * Gets last error message.
	 * @return Returns the message.
	 */
	public function getLastError() {
		return $this->_lastError;
	}
	/**
	 * @todo doc
	 * @return @todo doc
	 */
	public function ok() {
		return $this->_ok;
	}
	/**
	 * Sets last error message.
	 * @param $message Message to set.
	 * @param $autoFormat Auto apply message formating.
	 * @return Returns the message set.
	 */
	public function setLastError($message="", $autoFormat=true) {
		$this->_lastError = ($autoFormat ? $this->formatErrorMessage($message) : $message);
		$this->_ok        = false;
		return $this->getLastError();
	}

	/*
	 * Protected Methods
	 */

	/*
	 * Public class methods
	 */
	/**
	 * @return Returns the singleton instance of this class POCErrorsHolder.
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
