<?php
/**
 * @file POCStoredCodes.php
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

class POCStoredCodes {
	/**
	 * @var POCStoredCodes
	 */
	private static	$_Instance;

	/**
	 * @var POCErrorsHolder
	 */
	protected	$_errors;
	/**
	 * @var POCHistoryManager
	 */
	protected	$_history;
	/**
	 * @var bool
	 */
	protected	$_isLoaded;
	/**
	 * @var string
	 */
	protected	$_dbtype;

	protected function __construct() {
		global $wgDBtype;

		$this->_errors  = POCErrorsHolder::Instance();
		$this->_history = POCHistoryManager::Instance();

		$this->_isLoaded    = false;
		$this->_dbtype      = $wgDBtype;

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
	 * @todo doc
	 * @param $code @todo doc
	 * @return @todo doc
	 */
	public function getByCode($code) {
		$out = false;

		$this->_errors->clearError();
		$out = $this->selectByCode($code);
			
		return $out;
	}
	/**
	 * @todo doc
	 * @param $connection @todo doc
	 * @param $filepath @todo doc
	 * @param $revision @todo doc
	 * @return @todo doc
	 */
	public function getFile($connection, $filepath, $revision) {
		$out = false;

		$conn = POCSVNConnections::Instance()->getConnection($connection);
		if($conn) {
			global	$wgPieceOfCodeConfig;

			$this->_errors->clearError();
			$fileInfo = $this->selectFiles($connection, $filepath, $revision);

			/*
			 * If the code doesn't exists, it tries to download and
			 * stored it.
			 */
			if(!$fileInfo && $this->_errors->ok()) {
				global	$wgUser;

				/*
				 * Checking if there are enough permission to
				 * upload a new file.
				 */
				if($wgPieceOfCodeConfig['enableuploads'] && in_array('upload', $wgUser->getRights())) {
					/*
					 * Calculating unique code.
					 */
					$code   = md5("{$connection}{$revision}{$filepath}");//@fixme this must be centralized.
					/*
					* Calculating upload path and creating
					* every directory that is needed and
					* doesn't exist.
					*/
					$auxDir = $code[0].DIRECTORY_SEPARATOR.$code[0].$code[1].DIRECTORY_SEPARATOR;
					if(!is_dir($wgPieceOfCodeConfig['uploaddirectory'].DIRECTORY_SEPARATOR.$auxDir)) {
						mkdir($wgPieceOfCodeConfig['uploaddirectory'].DIRECTORY_SEPARATOR.$auxDir, 0755, true);
					}
					$uploadPath  = $auxDir.$code."_".$revision."_".basename($filepath);
					/*
					 * Calculationg downloading path.
					 */
					$svnPath     = $conn['url'].$filepath;
					/*
					 * Build font-code information.
					 */
					$auxFileInfo = array(
						'connection'	=> $connection,
						'code'		=> $code,
						'path'		=> $filepath,
						'revision'	=> $revision,
						'lang'		=> $this->getLangFromExtension($filepath),
						'upload_path'	=> $uploadPath,
						'user'		=> $wgUser->getName(),
					);

					/*
					 * Getting file from SVN.
					 */
					if($this->_errors->ok() && $this->getSVNFile($conn, $auxFileInfo)) {
						$this->insertFile($auxFileInfo);

						if($this->_errors->ok()) {
							if($out = $this->selectFiles($connection, $filepath, $revision)) {
								$this->_history->newDBCode($auxFileInfo['code'], "Adding file {$auxFileInfo['path']}:{$auxFileInfo['revision']} to database.");
							}
						}
					} else {
						if($this->_errors->ok()) {
							$this->_errors->setLastError(wfMsg('poc-errmsg-no-svn-file', $connection, $filepath, $revision));
						}
					}
				} else {
					$this->_errors->setLastError(wfMsg('poc-errmsg-no-upload-rights'));
				}
			} else {
				$out = $fileInfo;
			}
		} else {
			$this->_errors->setLastError(wfMsg('poc-errmsg-invalid-connection'));
		}

		return $out;
	}
	/**
	 * @todo doc
	 * @return @todo doc
	 */
	public function isLoaded() {
		return $this->_isLoaded;
	}
	/**
	 * @todo doc
	 * @param $code @todo doc
	 * @return @todo doc
	 */
	public function removeByCode($code) {
		$out = false;

		global	$wgPieceOfCodeConfig;

		$this->_errors->clearError();
		$fileInfo = $this->selectByCode($code);
		if($this->_errors->ok()) {
			global	$wgPieceOfCodeConfig;
			$upload_path = $wgPieceOfCodeConfig['uploaddirectory'].DIRECTORY_SEPARATOR.$fileInfo['upload_path'];
			unlink($upload_path);
			if(!is_readable($upload_path)) {
				$this->_history->deleteSVNCode($fileInfo['code'], "Removing file {$fileInfo['path']}:{$fileInfo['revision']} from disk.");

				$this->deleteByCode($code);
				if($out = $this->_errors->ok()) {
					$this->_history->deleteDBCode($fileInfo['code'], "Removing file {$fileInfo['path']}:{$fileInfo['revision']} from disk.");
				}
			} else {
				$this->_errors->setLastError(wfMsg('poc-errmsg-remove-file', $upload_path));
			}
		}
			
		return $out;
	}
	/**
	 * @todo doc
	 * @param $code @todo doc
	 * @return @todo doc
	 */
	public function selectByCode($code) {
		$out = null;

		if($this->_dbtype == 'mysql') {
			global	$wgPieceOfCodeConfig;

			$dbr = &wfGetDB(DB_SLAVE);
			$res = $dbr->select($wgPieceOfCodeConfig['db-tablename'], array('cod_id', 'cod_connection', 'cod_code', 'cod_path', 'cod_lang', 'cod_revision', 'cod_count', 'cod_upload_path', 'cod_user', 'cod_timestamp'),
			"cod_code = '{$code}'");
			if($row = $dbr->fetchRow($res)) {
				$out = array(
					'id'		=> $row[0],
					'connection'	=> $row[1],
					'code'		=> $row[2],
					'path'		=> $row[3],
					'lang'		=> $row[4],
					'revision'	=> $row[5],
					'count'		=> $row[6],
					'upload_path'	=> $row[7],
					'user'		=> $row[8],
					'timestamp'	=> $row[9]
				);
			} else {
				$this->_errors->setLastError(wfMsg('poc-errmsg-query-no-result'));
			}
		} else {
			$this->_errors->setLastError(wfMsg('poc-errmsg-unknown-dbtype', $this->_dbtype));
		}

		return $out;
	}
	/**
	 * @todo doc
	 * @param $connection @todo doc
	 * @param $filepath @todo doc
	 * @param $revision @todo doc
	 * @param $full @todo doc
	 * @return @todo doc
	 */
	public function selectFiles($connection=false, $filepath=false, $revision=false, $full=true) {
		$out = null;

		$multiple = ($connection === false || $filepath === false || $revision === false);

		if($this->_dbtype == 'mysql') {
			global	$wgPieceOfCodeConfig;

			$dbr = &wfGetDB(DB_SLAVE);
			global	$wgDBprefix;

			$qry =	"select  cod_id          as `id`,\n".
				"        cod_connection  as `connection`,\n".
				"        cod_code        as `code`,\n".
				"        cod_path        as `path`,\n".
				"        cod_lang        as `lang`,\n".
				"        cod_revision    as `revision`,\n".
				"        cod_count       as `count`,\n".
				"        cod_upload_path as `upload_path`,\n".
				"        cod_user        as `user`,\n".
				"        cod_timestamp   as `timestamp`\n".
				"from    {$wgDBprefix}{$wgPieceOfCodeConfig['db-tablename']}\n";

			if(!$multiple) {
				$qry.=	"where   cod_connection = '{$connection}'\n".
					" and    cod_path       = '{$filepath}'\n".
					" and    cod_revision   = '{$revision}'\n";
			}
			if(!$full) {
				$qry.=	"order by cod_count desc\n".
					"limit ".($wgPieceOfCodeConfig['show']['stored-limit']+1)."\n";
			}
			$res = $dbr->query($qry);
			if($multiple) {
				$out = array();
				while($row = $dbr->fetchRow($res)) {
					$out[] = $row;
				}
			} else {
				if($row = $dbr->fetchRow($res)) {
					$out = $row;
				}
			}
		} else {
			$this->_errors->setLastError(wfMsg('poc-errmsg-unknown-dbtype', $this->_dbtype));
		}

		return $out;
	}

	/*
	 * Protected Methods
	 */
	/**
	 * @todo doc
	 * @return @todo doc
	 */
	protected function createTable() {
		$out = false;

		if($this->_dbtype == 'mysql') {
			global	$wgDBprefix;
			global	$wgPieceOfCodeConfig;

			$dbr = &wfGetDB(DB_SLAVE);
			if(!$dbr->tableExists($wgPieceOfCodeConfig['db-tablename'])) {
				$sql =	"create table ".$wgDBprefix.$wgPieceOfCodeConfig['db-tablename']."(\n".
					"        cod_id             integer      not null auto_increment primary key,\n".
					"        cod_connection     varchar(20)  not null,\n".
					"        cod_code           varchar(40)  not null,\n".
					"        cod_path           varchar(255) not null,\n".
					"        cod_lang	    varchar(20)  not null default 'text',\n".
					"        cod_revision	    integer      not null default '-1',\n".
					"        cod_count	    integer      not null default '-1',\n".
					"        cod_upload_path    varchar(255) not null,\n".
					"        cod_user           varchar(40)  not null,\n".
					"        cod_timestamp      timestamp default current_timestamp\n".
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
	/**
	 * @todo doc
	 * @param $filename @todo doc
	 * @return @todo doc
	 */
	protected function getLangFromExtension($filename) {
		$out = '';

		global	$wgPieceOfCodeConfig;

		$pieces = explode('.', basename($filename));
		$len    = count($pieces);
		if($len > 1) {
			$ext    = strtolower($pieces[$len-1]);
			foreach($wgPieceOfCodeConfig['fontcodes'] as $type => $extList) {
				if(in_array($ext, $extList)) {
					$out = $type;
					break;
				}
			}
			if(!$out && in_array($ext, $wgPieceOfCodeConfig['fontcodes-forbidden'])) {
				$this->_errors->setLastError(wfMsg('poc-errmsg-forbidden-tcode', $pieces[count($pieces)-1]));
			} elseif(!$out) {
				$this->_errors->setLastError(wfMsg('poc-errmsg-unknown-tcode', $pieces[count($pieces)-1]));
				$out = 'text';
			}
		} elseif($wgPieceOfCodeConfig['fontcodes-allowempty']) {
			$out = 'text';
		} else {
			$this->_errors->setLastError(wfMsg('poc-errmsg-empty-tcode'));
		}

		return $out;
	}
	/**
	 * @todo doc
	 * @param $connInfo @todo doc
	 * @param $fileInfo @todo doc
	 * @return @todo doc
	 */
	protected function getSVNFile(&$connInfo, &$fileInfo) {
		$out = false;

		global	$wgPieceOfCodeConfig;
		/*
		 * Calculation full-path to the font-code file.
		 */
		$filepath = $wgPieceOfCodeConfig['uploaddirectory'].DIRECTORY_SEPARATOR.$fileInfo['upload_path'];
		/*
		 * If it's not available, it tries to download it.
		 */
		if(!is_readable($filepath)) {
			/*
			 * Building SVN comand.
			 */
			$command = $wgPieceOfCodeConfig['svn-binary']." ";
			$command.= "cat ";
			$command.= "\"{$connInfo['url']}{$fileInfo['path']}\" ";
			$command.= "-r{$fileInfo['revision']} ";
			if(isset($connInfo['username'])) {
				$command.= "--username {$connInfo['username']} ";
			}
			if(isset($connInfo['password'])) {
				$command.= "--password {$connInfo['password']} ";
			}
			$command.= "> \"{$filepath}\"";
			/*
			 * Attemting to download it running a SVN command.
			 */
			passthru($command, $error);

			if(!$error && is_readable($filepath)) {
				$this->_history->newSVNCode($fileInfo['code'], "Downloading file {$fileInfo['path']}:{$fileInfo['revision']} from SVN.");
				$out = true;
			} elseif($error && is_readable($filepath)) {
				unlink($filepath);
			} elseif(is_readable($filepath)) {
				$this->_errors->setLastError(wfMsg('poc-errmsg-svn-no-file', $filepath));
			}
		} else {
			$this->_errors->setLastError(wfMsg('poc-errmsg-svn-file-exist', $filepath));
		}

		return $out;
	}
	/**
	 * @todo doc
	 * @param $fileInfo @todo doc
	 * @return @todo doc
	 */
	protected function insertFile(&$fileInfo) {
		$out = false;

		if($this->_dbtype == 'mysql') {
			global	$wgPieceOfCodeConfig;

			if($this->_errors->ok()) {
				$dbr = &wfGetDB(DB_SLAVE);
				$res = $dbr->insert($wgPieceOfCodeConfig['db-tablename'],
				array(	'cod_connection'	=> $fileInfo['connection'],
					'cod_code'		=> $fileInfo['code'],
					'cod_path'		=> $fileInfo['path'],
					'cod_lang'		=> $fileInfo['lang'],
					'cod_revision'		=> $fileInfo['revision'],
					'cod_upload_path'	=> $fileInfo['upload_path'],
					'cod_user'		=> $fileInfo['user'],
				));
				if($res === true) {
					$out = true;
				} else {
					$this->_errors->setLastError(wfMsg('poc-errmsg-no-insert'));
				}
			}
		} else {
			$this->_errors->setLastError(wfMsg('poc-errmsg-unknown-dbtype', $this->_dbtype));
		}

		return	$out;
	}
	/**
	 * @todo doc
	 * @param $code @todo doc
	 * @return @todo doc
	 */
	protected function deleteByCode($code) {
		$out = null;

		if($this->_dbtype == 'mysql') {
			global	$wgPieceOfCodeConfig;

			$dbr = &wfGetDB(DB_SLAVE);
			$res = $dbr->delete($wgPieceOfCodeConfig['db-tablename'], array('cod_code' => $code));
			if($res !== true) {
				$this->_errors->setLastError(wfMsg('poc-errmsg-query-no-delete'));
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
	 * @return Returns the singleton instance of this class POCStoredCodes.
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
