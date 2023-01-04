<?php
/**
 * @file POCHistoryManager.php
 *
 * Subversion
 *	- ID:  $Id$
 *	- URL: $URL$
 *
 * @copyright 2010 Alejandro Darío Simi
 * @license GPL
 * @author Alejandro Darío Simi
 * @date 2010-09-16
 */

/**
 * @class POCHistoryManager
 * This class represents a list of flags stored in the data base.
 *
 * @author Alejandro Darío Simi
 */
class POCHistoryManager {
	/**
	 * @var POCHistoryManager
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
		global	$wgPieceOfCodeConfig;

		$this->_errors = POCErrorsHolder::Instance();
		$this->_flags  = POCFlags::Instance();

		$this->_dbtype	= $wgDBtype;

		if($wgPieceOfCodeConfig['history']) {
			$this->createTable();
		}
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
	public function __call($name, $values) {
		$out = false;

		$code = array_shift($values);

		$values['description'] = $values[0]; 
		switch($name) {
			case 'newDBCode':
				$out = $this->addEntry($code, 'NEW_DB_CODE', $values);
				break;
			case 'newSVNCode':
				$out = $this->addEntry($code, 'NEW_SVN_CODE', $values);
				break;
			case 'deleteDBCode':
				$out = $this->addEntry($code, 'DELETE_DB_CODE', $values);
				break;
			case 'deleteSVNCode':
				$out = $this->addEntry($code, 'DELETE_SVN_CODE', $values);
				break;
			default:
				echo "Unrecognized history entry type '{$name}'. ";
				die(__FILE__.":".__LINE__);
		}

		return $out;
	}

	/*
	 * Protected methods.
	 */
	/**
	 * @todo doc
	 * @param $code @todo doc
	 * @param $action @todo doc
	 * @param $params @todo doc
	 * @return Return true when the new entry is successfully insert in the
	 * databases. When history recording is disable. it always returns true.
	 */
	public function addEntry($code, $action, array $params=array()) {
		$out = false;

		global	$wgPieceOfCodeConfig;
		if($wgPieceOfCodeConfig['history']) {
			if($this->_dbtype == 'mysql') {
				global	$wgDBprefix;
				global	$wgUser;

				$dbr = &wfGetDB(DB_SLAVE);
				$sql =	"insert\n".
					"        into {$wgDBprefix}{$wgPieceOfCodeConfig['db-tablename-history']} (\n".
					"                hty_code, hty_action, hty_description, hty_user)\n".
					"        values ('{$code}', '{$action}', '".(isset($params['description'])?$params['description']:'')."', '".$wgUser->getName()."')";
				$err = $dbr->query($sql);
				if($err) {
					$out = true;
				} else {
					$this->_errors->setLastError(wfMsg('poc-errmsg-history-noinsert'));
				}
			} else {
				$this->_errors->setLastError(wfMsg('poc-errmsg-unknown-dbtype', $this->_dbtype));
			}
		}

		return $out;
	}
	public function getHistory($code=false, $full=false) {
		$out = array();

		global	$wgPieceOfCodeConfig;
		if($wgPieceOfCodeConfig['history']) {
			if($this->_dbtype == 'mysql') {
				global	$wgDBprefix;
				global	$wgUser;

				$dbr = &wfGetDB(DB_SLAVE);
				$sql =	"select  hty_code        as code,\n".
					"        hty_action      as action,\n".
					"        hty_description as description,\n".
					"        hty_user        as user,\n".
					"        hty_timestamp   as timestamp\n".
					"from    {$wgDBprefix}{$wgPieceOfCodeConfig['db-tablename-history']}\n";
				if($code) {
					$sql.= "where   hty_code = '{$code}'";
				}
				$sql.= "order by hty_timestamp desc\n";
				if(!$full) {
					$sql.= "limit {$wgPieceOfCodeConfig['show']['history-limit']}";
				}

				$res = $dbr->query($sql);

				if($err !== false) {
					while($row = $dbr->fetchRow($res)) {
						array_push($out, $row);
					}
				} else {
					$this->_errors->setLastError(wfMsg('poc-errmsg-history-noresult'));
				}
			} else {
				$this->_errors->setLastError(wfMsg('poc-errmsg-unknown-dbtype', $this->_dbtype));
			}
		}

		return $out;
	}

	/*
	 * Protected Methods
	 */
	/**
	 * This method checks existens for table of history storage.
	 * @return Returns true if there where no errors. Otherwise, false.
	 */
	protected function createTable() {
		$out = false;

		if($this->_dbtype == 'mysql') {
			global	$wgDBprefix;
			global	$wgPieceOfCodeConfig;

			$dbr = &wfGetDB(DB_SLAVE);
			if(!$dbr->tableExists($wgPieceOfCodeConfig['db-tablename-history'])) {
				$sql =	"create table ".$wgDBprefix.$wgPieceOfCodeConfig['db-tablename-history']."(\n".
					"        hty_code           varchar(40)  not null,\n".
					"        hty_action	    varchar(20)  not null,\n".
					"        hty_description    varchar(255) not null default '',\n".
					"        hty_user           varchar(40)  not null default '',\n".
					"        hty_timestamp      timestamp default current_timestamp,\n".
					"        index(hty_code)\n".
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
	 * @return Returns the singleton instance of this class
	 * POCHistoryManager.
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
