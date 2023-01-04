<?php
/**
 * @file POCStats.php
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
 * @class POCStats
 *
 * @todo doc
 */
class POCStats {
	/**
	 * @var POCStats
	 */
	private static	$_Instance;

	/**
	 * @var POCErrorsHolder
	 */
	protected	$_errors;
	/**
	 * @var string
	 */
	protected	$_dbtype;

	protected function __construct() {
		global $wgDBtype;

		$this->_errors = POCErrorsHolder::Instance();

		$this->_isLoaded    = false;
		$this->_dbtype      = $wgDBtype;

		if($this->isEnabled()) {
			$this->createTables();
			$this->removeObsolete();
			$this->updateNews();
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
	/**
	 * @todo doc
	 * @param $code @todo doc
	 * @return @return @todo doc
	 */
	public function getCodePages($code) {
		$out = false;

		global	$wgPieceOfCodeConfig;

		if($this->_dbtype == 'mysql') {
			global	$wgDBprefix;

			$globalCodes = array();

			$dbr = &wfGetDB(DB_SLAVE);

			$sql =	"select	 cps_code       as code,\n".
				"        page_id,\n".
				"        cps_times      as times,\n".
				"        page_title     as title,\n".
				"        page_namespace as namespace,\n".
				"        rev_user_text  as last_user\n".
				"from    {$wgDBprefix}{$wgPieceOfCodeConfig['db-tablename-ccounts']} inner join `{$wgDBprefix}revision`\n".
				"                on (cps_text_id = rev_text_id)\n".
				"        inner join `{$wgDBprefix}page`\n".
				"                on (rev_id = page_latest)\n".
				"where   cps_code = '{$code}'";
			$res = $dbr->query($sql);

			$out = array();
			while(($row = $dbr->fetchRow($res))) {
				$out[] = $row;
			}
		} else {
			$this->_errors->setLastError(wfMsg('poc-errmsg-unknown-dbtype', $this->_dbtype));
		}

		return $out;
	}
	/**
	 * @todo doc
	 * @return @todo doc
	 */
	public function isEnabled() {
		global	$wgPieceOfCodeConfig;
		return $wgPieceOfCodeConfig['stats'];
	}

	/*
	 * Protected Methods
	 */
	/**
	 * This methods launches all needed tables creations.
	 * @return Returns true when there were no errors.
	 */
	protected function createTables() {
		$out = true;

		$out&= $this->createTablePagesList();
		$out&= $this->createTableCodeAndPages();

		return $out;
	}
	/**
	 * @todo doc
	 * return @todo doc
	 */
	protected function createTableCodeAndPages() {
		$out = false;

		if($this->_dbtype == 'mysql') {
			global	$wgDBprefix;
			global	$wgPieceOfCodeConfig;

			$dbr = &wfGetDB(DB_SLAVE);
			if(!$dbr->tableExists($wgPieceOfCodeConfig['db-tablename-ccounts'])) {
				$sql =	"create table {$wgDBprefix}{$wgPieceOfCodeConfig['db-tablename-ccounts']} (\n".
					"        cps_code       varchar(40) not null,\n".
					"        cps_text_id    int(10) unsigned not null,\n".
					"        cps_times      int(10) unsigned not null default '1',\n".
					"        cps_timestamp  timestamp not null default current_timestamp,\n".
					"        primary key (cps_code, cps_text_id)\n".
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
	 * return @todo doc
	 */
	protected function createTablePagesList() {
		$out = false;

		if($this->_dbtype == 'mysql') {
			global	$wgDBprefix;
			global	$wgPieceOfCodeConfig;

			$dbr = &wfGetDB(DB_SLAVE);
			if(!$dbr->tableExists($wgPieceOfCodeConfig['db-tablename-texts'])) {
				$sql =	"create table {$wgDBprefix}{$wgPieceOfCodeConfig['db-tablename-texts']} (\n".
					"        plst_text_id    int(10) unsigned not null primary key,\n".
					"        plst_page_id    int(10) unsigned not null,\n".
					"        plst_scanned    boolean not null default false,\n".
					"        plst_use_poc    boolean not null default false,\n".
					"        plst_timestamp  timestamp not null default current_timestamp\n".
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
	 * @return @todo doc
	 */
	protected function removeObsolete() {
		$out = true;

		$out&=$this->removeObsoleteTexts();
		$out&=$this->removeObsoleteCodeAndPages();

		return $out;
	}
	/**
	 * @todo doc
	 * @return @todo doc
	 */
	protected function removeObsoleteCodeAndPages() {
		$out = false;

		if($this->_dbtype == 'mysql') {
			global	$wgDBprefix;
			global	$wgPieceOfCodeConfig;

			$dbr = &wfGetDB(DB_SLAVE);
			$sql =	"select  cps_code, cps_text_id\n".
				"from	 {$wgDBprefix}{$wgPieceOfCodeConfig['db-tablename-ccounts']}\n".
				"where   cps_text_id not in (\n".
				"                select  plst_text_id\n".
				"                from    {$wgDBprefix}{$wgPieceOfCodeConfig['db-tablename-texts']})\n".
				" or     cps_timestamp < (sysdate()-{$wgPieceOfCodeConfig['db-stats-timelimit']})";
			$res = $dbr->query($sql);

			$i = 0;
			$j = 1;
			if($wgPieceOfCodeConfig['db-stats-limited']) {
				$limit = $wgPieceOfCodeConfig['db-stats-per-try'];
			} else {
				$limit = 1;
				$j     = 0;
			}
			while(($row = $dbr->fetchRow($res)) && $i < $limit) {
				$sql =	"delete from     {$wgDBprefix}{$wgPieceOfCodeConfig['db-tablename-ccounts']}\n".
					"where           cps_code    = '{$row['cps_code']}'".
					" and            cps_text_id = '{$row['cps_text_id']}'";
				$err = $dbr->query($sql);
				$i+=$j;
			}
		} else {
			$this->_errors->setLastError(wfMsg('poc-errmsg-unknown-dbtype', $this->_dbtype));
		}

		return $out;
	}
	/**
	 * @todo doc
	 * @return @todo doc
	 */
	protected function removeObsoleteTexts() {
		$out = false;

		if($this->_dbtype == 'mysql') {
			global	$wgDBprefix;
			global	$wgPieceOfCodeConfig;

			$dbr = &wfGetDB(DB_SLAVE);
			$sql =	"select  plst_text_id\n".
				"from    {$wgDBprefix}{$wgPieceOfCodeConfig['db-tablename-texts']}\n".
				"where   plst_text_id not in (\n".
				"                select  plst_text_id\n".
				"                from    {$wgDBprefix}{$wgPieceOfCodeConfig['db-tablename-texts']} inner join `{$wgDBprefix}revision`\n".
				"                                on (plst_text_id = rev_text_id)\n".
				"                        inner join `{$wgDBprefix}page`\n".
				"                                on (rev_id = page_latest))\n".
				" or     plst_timestamp < (sysdate()-{$wgPieceOfCodeConfig['db-stats-timelimit']})";
			$res = $dbr->query($sql);

			$i = 0;
			$j = 1;
			if($wgPieceOfCodeConfig['db-stats-limited']) {
				$limit = $wgPieceOfCodeConfig['db-stats-per-try'];
			} else {
				$limit = 1;
				$j     = 0;
			}
			while(($row = $dbr->fetchRow($res)) && $i < $limit) {
				$sql =	"delete from     {$wgDBprefix}{$wgPieceOfCodeConfig['db-tablename-texts']}\n".
					"where           plst_text_id = '{$row['plst_text_id']}'";
				$err = $dbr->query($sql);
				$i+=$j;
			}
		} else {
			$this->_errors->setLastError(wfMsg('poc-errmsg-unknown-dbtype', $this->_dbtype));
		}

		return $out;
	}
	/**
	 * @todo doc
	 * @return @todo doc
	 */
	protected function updateNews() {
		$out = true;

		$out&=$this->updateNewTexts();
		$out&=$this->updateCodesAndPages();
		$out&=$this->updateZeroCounts();

		return $out;
	}
	/**
	 * @todo doc
	 * @return @todo doc
	 */
	protected function updateCodesAndPages() {
		$out = false;

		if($this->_dbtype == 'mysql') {
			global	$wgDBprefix;
			global	$wgPieceOfCodeConfig;

			$globalCodes = array();

			$dbr = &wfGetDB(DB_SLAVE);
			$sql =	"select  old_text, old_id\n".
				"from    `{$wgDBprefix}{$wgPieceOfCodeConfig['db-tablename-texts']}` inner join `{$wgDBprefix}text`\n".
				"                on (plst_text_id = old_id)\n".
				"where   plst_use_poc = true\n".
				" and    plst_text_id not in (\n".
				"                select  cps_text_id\n".
				"                from    `{$wgDBprefix}{$wgPieceOfCodeConfig['db-tablename-ccounts']}`)\n";
			if($wgPieceOfCodeConfig['db-stats-limited']) {
				$sql.="limit {$wgPieceOfCodeConfig['db-stats-per-try']}\n";
			}
			$res = $dbr->query($sql);

			while(($row = $dbr->fetchRow($res))) {
				$i              = 0;
				$matches        = array();
				$list		= array();
				$enterSeparator = '___ENTER_CHARACTER___';

				/*
				 * Loading tag usage and breaking it into
				 * pieces.
				 */
				preg_match_all('/(<pieceofcode[^>]*>)(.*?)(<\/pieceofcode>)/', str_replace("\n", $enterSeparator, $row['old_text']), $matches);
				/*
				 * Loadint tag attributes.
				 */
				$params     = array();
				$parseinput = array();
				foreach($matches[1] as $mk => $conf) {
					$conf = str_replace(array("pieceofcode","\t","<",">"), array(' ','','',''), $conf);
					preg_match_all('/([a-z]+)\=\"([a-z]+)\"/', $conf, $pmatches);
					$params[$mk] = array();
					foreach($pmatches[1] as $k => $pm) {
						$params[$mk][trim($pm)] = trim($pmatches[2][$k]);
					}
					unset($pmatches);
					/*
					 * Checking attribute 'parseinput'.
					 */
					$parseinput[$mk] = (isset($params[$mk]['parseinput']) && ($params[$mk]['parseinput']=='parseinput'||$params[$mk]['parseinput']='true'));
				}

				foreach($matches[2] as $mk => $conf) {
					$conf = str_replace($enterSeparator, "\n", $conf);
					/*
					 * Applying recursive parsing if it needed.
					 */
					if($parseinput[$mk]) {
						global	$wgParser;
						global	$wgTitle;
						/*
						 * @fixme this is not working, check way.
						 */
						//$conf = $wgParser->preprocess($conf, $wgParser->getTitle(), $wgParser->getOptions());
					}
					$list[$i] = explode("\n", $conf);

					foreach($list[$i] as $k => $l) {
						if(trim($l)) {
							$aux  = explode('=', $l);
							$aux2 = trim($aux[1]);
							if($aux2) {
								$list[$i][trim($aux[0])] = $aux2;
							}
						}
						unset($list[$i][$k]);
					}
					$i++;
				}
				$codes = array();
				foreach($list as $k => $l) {
					$code = md5("{$l['connection']}{$l['revision']}{$l['file']}");	//@fixme this must be centralized.
					@$codes[$code]++;
					@$globalCodes[$code]++;
				}
				foreach($codes as $k => $c) {
					$sql =	"insert\n".
						"        into {$wgDBprefix}{$wgPieceOfCodeConfig['db-tablename-ccounts']} (\n".
						"                cps_code, cps_text_id, cps_times)\n".
						"        values ('{$k}','{$row['old_id']}','{$c}')\n".
						"                on duplicate key\n".
						"                        update cps_times     = '{$c}',".
						"                               cps_timestamp = sysdate()";
					$err = $dbr->query($sql);
				}
			}
			foreach($globalCodes as $k => $c) {
				$sql =	"update  {$wgDBprefix}{$wgPieceOfCodeConfig['db-tablename']}\n".
					"set     cod_count = (\n".
					"                select  sum(cps_times)\n".
					"                from    `{$wgDBprefix}{$wgPieceOfCodeConfig['db-tablename-ccounts']}`\n".
					"                where cps_code = '{$k}')\n".
					"where   cod_code  = '{$k}'";
				$err = $dbr->query($sql);
			}
		} else {
			$this->_errors->setLastError(wfMsg('poc-errmsg-unknown-dbtype', $this->_dbtype));
		}

		return $out;
	}
	/**
	 * @todo doc
	 * @return @todo doc
	 */
	protected function updateNewTexts() {
		$out = false;

		if($this->_dbtype == 'mysql') {
			global	$wgDBprefix;
			global	$wgPieceOfCodeConfig;

			$dbr = &wfGetDB(DB_SLAVE);
			$sql =	"insert\n".
				"        into `{$wgDBprefix}{$wgPieceOfCodeConfig['db-tablename-texts']}`(plst_text_id, plst_page_id)\n".
				"        select  rev_text_id, page_id\n".
				"        from    `{$wgDBprefix}page` inner join `{$wgDBprefix}revision`\n".
				"                        on (page_latest = rev_id)\n".
				"        where   rev_text_id not in (\n".
				"                        select  plst_text_id\n".
				"                        from    `{$wgDBprefix}{$wgPieceOfCodeConfig['db-tablename-texts']}`)\n";
			$error = $dbr->query($sql);
			if($error === true) {
				$out = true;
			} else {
				die(__FILE__.":".__LINE__);
			}
			if($out) {
				$sql =	"select  *\n".
					"from    `{$wgDBprefix}{$wgPieceOfCodeConfig['db-tablename-texts']}`\n".
					"where   plst_scanned = false\n";
				if($wgPieceOfCodeConfig['db-stats-limited']) {
					$sql.="limit 0, {$wgPieceOfCodeConfig['db-stats-per-try']}\n";
				}
				$res = $dbr->query($sql);
				while($row = $dbr->fetchRow($res)) {
					$sql =	"select  *\n".
						"from    `{$wgDBprefix}text`\n".
						"where   old_id = '{$row['plst_text_id']}'\n".
						" and    old_text like '%</pieceofcode>%'\n";
					$auxRes = $dbr->query($sql);
					$sql =	"update  `{$wgDBprefix}{$wgPieceOfCodeConfig['db-tablename-texts']}`\n".
						"set     plst_scanned   = true,\n".
						"        plst_use_poc   = ".($dbr->numRows($auxRes)>0?'true':'false').",\n".
						"        plst_timestamp = sysdate()\n".
						"where   plst_text_id = '{$row['plst_text_id']}'\n";
						" and    plst_page_id = '{$row['plst_page_id']}'\n";
					$err = $dbr->query($sql);
				}
			}
		} else {
			$this->_errors->setLastError(wfMsg('poc-errmsg-unknown-dbtype', $this->_dbtype));
		}

		return $out;
	}
	/**
	 * @todo doc
	 * @return @todo doc
	 */
	protected function updateZeroCounts() {
		$out = false;

		if($this->_dbtype == 'mysql') {
			global	$wgDBprefix;
			global	$wgPieceOfCodeConfig;

			$dbr = &wfGetDB(DB_SLAVE);
			$sql =	"select  cod_code\n".
				"from    {$wgDBprefix}{$wgPieceOfCodeConfig['db-tablename']}\n".
				"where   cod_code not in (\n".
				"                select  cod_code\n".
				"                from    {$wgDBprefix}{$wgPieceOfCodeConfig['db-tablename']}\n".
				"                                inner join {$wgDBprefix}{$wgPieceOfCodeConfig['db-tablename-ccounts']}\n".
				"                                        on (cod_code = cps_code))\n";
			if($wgPieceOfCodeConfig['db-stats-limited']) {
				$sql.="limit 0, {$wgPieceOfCodeConfig['db-stats-per-try']}\n";
			}
			$res = $dbr->query($sql);

			while(($row = $dbr->fetchRow($res))) {
				$sql =	"update  {$wgDBprefix}{$wgPieceOfCodeConfig['db-tablename']}\n".
					"set     cod_count = '0'\n".
					"where   cod_code  = '{$row['cod_code']}'";
				$err = $dbr->query($sql);
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
	 * @return Returns the singleton instance of this class POCStats.
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
