<?php
/**
 * @file POCFlags.php
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

/**
 * @class POCFlags
 * This class represents a list of flags stored in the data base.
 * 
 * @author Alejandro Darío Simi
 */
class POCFlags {
	/**
	 * @var POCFlags
	 */
	private static	$_Instance;

	/**
	 * @var POCErrorsHolder
	 */
	protected	$_errors;
	/**
	 * @var array
	 */
	protected	$_flags;
	/**
	 * @var string
	 */
	protected	$_dbtype;

	protected function __construct() {
		global $wgDBtype;

		$this->_errors = POCErrorsHolder::Instance();

		$this->_flags	= array();
		$this->_dbtype	= $wgDBtype;

		$this->createTable();
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
	 * This method retrieves a value from the data base.
	 * @param $code Identifier for the flag to retieve.
	 * @param $reload This flag force to reload from database. This is
	 * needed because POCFlags holds a cache.
	 * @return Returns the value found.
	 */
	public function get($code, $reload=false) {
		$out = false;

		if(!isset($this->_flags[$code]) || $reload) {
			global	$wgPieceOfCodeConfig;

			$this->_errors->clearError();
			if($this->_dbtype == 'mysql') {
				global	$wgPieceOfCodeConfig;

				$dbr = &wfGetDB(DB_SLAVE);
				$res = $dbr->select($wgPieceOfCodeConfig['db-tablename-flags'], array('flg_code', 'flg_type', 'flg_bvalue', 'flg_ivalue', 'flg_float', 'flg_svalue'),
					"flg_code = '{$code}'");
				if($row = $dbr->fetchRow($res)) {
					/*
					 * @fixme cast values.
					 */
					switch($row['flg_type']) {
						case 'B':
							$this->_flags[$code] = $row['flg_bvalue'];
							break;
						case 'I':
							$this->_flags[$code] = $row['flg_ivalue'];
							break;
						case 'F':
							$this->_flags[$code] = $row['flg_fvalue'];
							break;
						case 'S':
							$this->_flags[$code] = $row['flg_svalue'];
							break;
					}
					$out = $this->_flags[$code];
				}
			} else {
				$this->_errors->setLastError(wfMsg('poc-errmsg-unknown-dbtype', $this->_dbtype));
			}
		} else {
			$out = $this->_flags[$code];
		}
			
		return $out;
	}
	/**
	 * This method set a flag on database. It it doesn't exists, it creates
	 * it.
	 * @param $code Flag identification code.
	 * @param $value Value to be set.
	 * @param $type Type of value. When it's false, it tries to auto-detect
	 * type of value.
	 */
	public function set($code, $value, $type=false) {
		if($this->_dbtype == 'mysql') {
			global	$wgPieceOfCodeConfig;

			$row   = array();
			$fType = 'flg_ivalue';
			/*
			 * Type auto-detection.
			 */
			if($type === false) {
				if(is_string($value)) {
					$type = 'S';
				} elseif(is_bool($value)) {
					$type = 'B';
				} elseif(is_int($value)) {
					$type = 'I';
				} else {
					$type = 'F';
				}
			}
			/*
			 * Selecting field to store values.
			 */
			switch($type) {
				case 'B':
					$row['flg_bvalue'] = $value;
					$fType             = 'flg_bvalue';
					break;
				case 'I':
					$row['flg_ivalue'] = $value;
					$fType             = 'flg_ivalue';
					break;
				case 'F':
					$row['flg_fvalue'] = $value;
					$fType             = 'flg_fvalue';
					break;
				case 'S':
					$row['flg_svalue'] = $value;
					$fType             = 'flg_svalue';
					break;
			}
			$row['flg_code'] = $code;
			$row['flg_type'] = $type;

			if($this->_errors->ok()) {
				global	$wgDBprefix;

				$dbr = &wfGetDB(DB_SLAVE);
				$sql =	"insert\n".
					"        into {$wgDBprefix}{$wgPieceOfCodeConfig['db-tablename-flags']} (\n".
					"                flg_code, flg_type, {$fType})\n".
					"        values ('{$row['flg_code']}', '{$row['flg_type']}', '{$row[$fType]}')\n".
					"                on duplicate key\n".
					"                        update  {$fType} = '{$row[$fType]}'";
				$res = $dbr->query($sql);
				if($res === true) {
					$out = true;
				} else {
					$this->_errors->setLastError(wfMsg('poc-errmsg-no-insert'));
				}
			}
		} else {
			$this->_errors->setLastError(wfMsg('poc-errmsg-unknown-dbtype', $this->_dbtype));
		}

	}

	/*
	 * Protected Methods
	 */
	/**
	 * This method checks existens for table of flags. 
	 * @return Returns true if there where no errors. Otherwise, false.
	 */
	protected function createTable() {
		$out = false;

		if($this->_dbtype == 'mysql') {
			global	$wgDBprefix;
			global	$wgPieceOfCodeConfig;

			$dbr = &wfGetDB(DB_SLAVE);
			if(!$dbr->tableExists($wgPieceOfCodeConfig['db-tablename-flags'])) {
				$sql =	"create table ".$wgDBprefix.$wgPieceOfCodeConfig['db-tablename-flags']."(\n".
					"        flg_code           varchar(20)               not null primary key,\n".
					"        flg_type	    enum ('B', 'I', 'S', 'F') not null default 'S',\n".
					"        flg_bvalue         boolean                   not null default false,\n".
					"        flg_ivalue         integer                   not null default '0',\n".
					"        flg_float          float                     not null default '0',\n".
					"        flg_svalue         varchar(255)              not null default '',\n".
					"        flg_timestamp      timestamp default current_timestamp\n".
					")";
				$error = $dbr->query($sql);
				if($error === true) {
					$out = true;
				} else {
					die(__FILE__.":".__LINE__);
				}
			} else {
				$out = true;
			}
		} else {
			$this->_errors->setLastError(wfMsg('poc-errmsg-unknown-dbtype', $this->_dbtype));
		}

		return $out;
	}

	/*
	 * Public class methods
	 */
	/**
	 * @return Returns the singleton instance of this class POCFlags.
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
