<?php

/**
 * Represents a configuration for a specific wiki
 * Created on Jul 20, 2006
 *
 * @author Gregory Szorc <gregory.szorc@gmail.com>
 */


use Wikimedia\Rdbms\DBConnectionError;
use Wikimedia\Rdbms\Database;

class MediaWikiFarmer_Wiki {

	/** Name of wiki */
	protected $_name;

	protected $_title;

	protected $_description;

	/** Username of person who created wiki */
	protected $_creator;

	/** Extensions to load for this wiki */
	protected $_extensions = [];

	/** Global variables set for this wiki */
	protected $_variables = [];

	/** Permissions are so funky, we give them their own variable */
	protected $_permissions = [ '*' => [], 'user' => [] ];

	/** DB object */
	protected $_db;


	/**
	 * Creates a wiki instance from a wiki name
	 * @param string $wiki
	 * @param array $variables
	 */
	public function __construct( $wiki, $variables = [] ) {
		$this->_name = $wiki;
		$this->_variables = $variables;
	}

	public function __get( $key ) {
		if ( substr( $key, 0, 2 ) == 'wg' ) {
			return isset( $this->_variables[$key] ) ? $this->_variables[$key] : null;
		}

		$property = '_' . $key;

		return isset( $this->$property ) ? $this->$property : null;
	}

	public function __set( $k, $v ) {
		if ( in_array( $k, [ 'name', 'title', 'description', 'creator', 'extensions' ] ) ) {
			$property = '_' . $k;
			$this->$property = $v;
		} elseif ( substr( $k, 0, 2 ) == 'wg' ) {
			$this->_variables[$k] = $v;
		}
	}

	/**
	 * How to represent this object as a string
	 * @return string
	 */
	public function __toString() {
		return $this->_name;
	}


	public static function factory( $wiki, $variables = [] ) {
		$farmer = MediaWikiFarmer::getInstance();

		if ( $farmer->useDatabase() ) {
			$dbr = $farmer->getDB( DB_REPLICA );
			$row = $dbr->selectRow( 'farmer_wiki', '*', [ 'fw_name' => $wiki ], __METHOD__ );
			if ( $row === false ) {
				return new MediaWikiFarmer_Wiki( $wiki, $variables );
			} else {
			    global $wgSitename;
			    $wgSitename = $row->fw_title;
				return self::newFromRow( $row );
			}
		} else {
			$file = self::_getWikiConfigFile( $wiki );

			if ( is_readable( $file ) ) {
				$content = file_get_contents( $file );
				$obj = unserialize( $content );
				if ( $obj instanceof MediaWikiFarmer_Wiki ) {
					return $obj;
				} else {
					throw new MWException( 'Stored wiki is corrupt.' );
				}
			} else {
				return new MediaWikiFarmer_Wiki( $wiki, $variables );
			}
		}
	}

	/**
	 * Create a new wiki from settings
	 * @param string $name
	 * @param string $title
	 * @param string $description
	 * @param string $creator
	 * @param array $variables
	 * @return MediaWikiFarmer
	 */
	public static function newFromParams(
			$name, $title, $description, $creator, $variables = []
		) {
		$wiki = self::factory( $name, $variables );

		$wiki->title = $title;
		$wiki->description = $description;
		$wiki->creator = $creator;

		return $wiki;
	}

	public static function newFromRow( $row ) {
		$wiki = new self( $row->fw_name );
		$wiki->_title = $row->fw_title;
		$wiki->_description = $row->fw_description;
		$wiki->_creator = $row->fw_creator;
		$wiki->_variables = unserialize( $row->fw_parameters );
		$wiki->_permissions = unserialize( $row->fw_permissions );

		$dbr = MediaWikiFarmer::getInstance()->getDB( DB_REPLICA );
		$res = $dbr->select(
			[ 'farmer_extension', 'farmer_wiki_extension' ],
			'*',
			[ 'fwe_wiki' => $row->fw_id ],
			__METHOD__,
			[],
			[ 'farmer_wiki_extension' => [ 'LEFT JOIN', 'fwe_extension = fe_id' ] ]
		);
		$wiki->_extensions = [];
		foreach ( $res as $row ) {
			$wiki->_extensions[$row->fe_name] = MediaWikiFarmer_Extension::newFromRow( $row);
        }

        return $wiki;
    }

    protected $tmpDBprefix;
    public function pushDBprefix(string $newDBprefix)
    {
        global $wgDBprefix;
        $this->tmpDBprefix = $wgDBprefix;
        return $newDBprefix;
    }
    
    public function popDBprefix()
    {
        return $this->tmpDBprefix;
    }
    
    public function create()
    {
        $farmer = MediaWikiFarmer::getInstance();

        // save the database prefix accordingly
        $this->wgDefaultSkin = $farmer->defaultSkin;

        // before we create the database, make sure this database doesn't really exist yet
        if (! $this->exists() && ! $this->databaseExists()) {
            global $wgDBprefix, $wgDBname;
            
            $wgDBprefix = $this->pushDBprefix($this->_name . $farmer->dbTablePrefixSeparator);
            
            $db2 = $this->getDatabase();
            $db2->tablePrefix($wgDBprefix);
            $db2->selectDomain($wgDBname);

            $this->save();
            $this->createDatabase();
            $farmer->updateFarmList();
            
            $wgDBprefix = $this->popDBprefix();
            $db2->tablePrefix($wgDBprefix);
        } else {
            throw new MWException(wfMessage('farmer-error-exists')->rawParams($this->_name )->escaped());
		}
	}

	/**
	 * Returns whether this wiki exists
	 *
	 * Simply looks for file presence.  We don't have to clear the stat cache
	 * because if a file doesn't exist, this isn't stored in the stat cache
	 * @return bool
	 */
	public function exists() {
		$farmer = MediaWikiFarmer::getInstance();

		if ( $farmer->useDatabase() ) {
			return (bool)$farmer->getDB( DB_REPLICA )->selectField(
					'farmer_wiki', 1, [ 'fw_name' => $this->_name ], __METHOD__
				);
		} else {
			return file_exists( self::_getWikiConfigFile( $this->_name ) );
		}
	}

	public function save() {
		$farmer = MediaWikiFarmer::getInstance();

		if ( $farmer->useDatabase() ) {
			$dbw = $farmer->getDB( DB_MASTER );
			$new = [
				'fw_name' => $this->_name,
				'fw_title' => $this->_title,
				'fw_description' => $this->_description,
				'fw_creator' => $this->_creator,
				'fw_parameters' => serialize( $this->_variables ),
				'fw_permissions' => serialize( $this->_permissions ),
			];

			$curId = $dbw->selectField( 'farmer_wiki', 'fw_id', [
					'fw_name' => $this->_name ], __METHOD__
			);

			if ( $curId == null ) {
				$dbw->insert( 'farmer_wiki', $new, __METHOD__ );
				$curId = $dbw->insertId();
			} else {
				$dbw->update( 'farmer_wiki', $new, [ 'fw_id' => $curId ], __METHOD__ );
			}

			$insert = [];
			foreach ( $this->_extensions as $ext ) {
				$insert[] = [ 'fwe_wiki' => $curId, 'fwe_extension' => $ext->id ];
			}
			$dbw->delete( 'farmer_wiki_extension', [ 'fwe_wiki' => $curId ], __METHOD__ );
			$dbw->insert( 'farmer_wiki_extension', $insert, __METHOD__ );

			return true;
		} else {
			$content = serialize( $this );
			return ( file_put_contents(
					self::_getWikiConfigFile( $this->_name ), $content, LOCK_EX
				) == strlen( $content )
			);
		}
	}

	public function delete() {
		if ( !$this->exists() ) {
			return;
		}

		$farmer = MediaWikiFarmer::getInstance();

		if ( $farmer->useDatabase() ) {
			$dbw = $farmer->getDB( DB_MASTER );
			$dbw->deleteJoin( 'farmer_wiki_extension', 'farmer_wiki', 'fwe_wiki', 'fw_id', [
					'fw_name' => $this->_name
				], __METHOD__
			);
			$dbw->delete( 'farmer_wiki', [ 'fw_name' => $this->_name ], __METHOD__ );
		} else {
			unlink( self::_getWikiConfigFile( $this->_name ) );
		}
	}

	public function databaseExists() {
		try {
		    $farmer = MediaWikiFarmer::getInstance();
			$db = $this->getDatabase();
			$db->tablePrefix($this->_name . $farmer->dbTablePrefixSeparator);
			return $db->tableExists( 'page' );
		} catch ( Exception $e ) {
			return false;
		}
	}

	/**
	 * Performs actions necessary to initialize the environment so MediaWiki can
	 * use this wiki
	 */
	public function initialize() {
	    
	    global $wgSkipSkins, $wgDefaultWiki, $wgScriptPath, $wgStylePath, $wgUploadDirectory, $wgUploadPath, $wgTmpDirectory, $wgGroupPermissions;
	    
		// loop over defined variables and set them in the global scope
		foreach ( $this->_variables as $k => $v ) {
			$GLOBALS[$k] = $v;
		}

		// we need to bring some global variables into scope so we can load extensions properly
		extract( $GLOBALS, EXTR_REFS );
		
		$farmer = MediaWikiFarmer::getInstance();
		
		//fix skin
		$skins = Skin::getSkinNames();
		foreach ( $wgSkipSkins as $skin ) {
		    if ( array_key_exists( $skin, $skins ) ) {
		        unset( $skins[$skin] );
		    }
		}
		if (array_key_exists($wgDefaultWiki, $skins) == false) {
		    $wgDefaultWiki = $farmer->defaultSkin;
		}

		// register all the extensions
		foreach ( $this->_extensions as $extension ) {
			foreach ( $extension->includeFiles as $file ) {
				require_once $file;
			}
		}
	
		//fix $wikiName
		$wikiName = $this->name;
		if ($wikiName == $farmer->defaultWiki) 
		    $wikiName = 'wiki';
		
	    //fix $wgScriptPath
		$wgScriptPath = str_replace("$1", $wikiName, $farmer->scriptUrl);
		
		//fix $wgStylePath
		$wgStylePath = str_replace("$1", $wikiName, $farmer->styleUrl);
		
		//fix $wgUploadDirectory
		$wgUploadDirectory = str_replace("$1", $wikiName, $farmer->uploadPath);
		
		//fix $wgUploadPath
		$wgUploadPath = str_replace("$1", $wikiName, $farmer->uploadUrl);
	
		//fix $wgTmpDirectory
		$tmpDir = str_replace("$1", $wikiName, $farmer->tmpPath);
		if (is_dir($tmpDir) == false)
		    mkdir($tmpDir,0777,true);
	    $wgTmpDirectory = $tmpDir;
		    
	    //fix $wgLogo
		if (file_exists(str_replace("$1", $wikiName, $farmer->logoPath)))
		    $wgLogo = str_replace("$1", $wikiName, $farmer->logoUrl);
		
	    //fix $wgDBprefix
	    if ($wikiName == 'wiki') {
	        $wgDBprefix = $farmer->defaultWiki . $farmer->dbTablePrefixSeparator;
	    } else {
	        $wgDBprefix = $wikiName . $farmer->dbTablePrefixSeparator;
	    }	

		// we allocate permissions to the necessary groups

		foreach ( $this->_permissions['*'] as $k => $v ) {
			$wgGroupPermissions['*'][$k] = $v;
		}

		foreach ( $this->_permissions['user'] as $k => $v ) {
			$wgGroupPermissions['user'][$k] = $v;
		}

		$wgGroupPermissions['sysop']['read'] = true;

		// assign permissions to administrators of this wiki
		if ( $farmer->sharingGroups() ) {
			$group = '[farmer][' . $this->_name . '][admin]';

			$grantToWikiAdmins = [ 'read', 'edit' ];

			foreach ( $grantToWikiAdmins as $v ) {
				$wgGroupPermissions[$group][$v] = true;
			}
		}

		$callback = $farmer->initCallback();
		if ( $callback ) {
			if ( is_callable( $callback ) ) {
				call_user_func( $callback, $this );
			} else {
				trigger_error( '$wgFarmerSettings[\'initCallback\'] is not callable', E_USER_WARNING );
			}
		}
	}


	protected static function _getWikiConfigPath() {

		$farmer = MediaWikiFarmer::getInstance();
		return $farmer->getConfigPath() . '/wikis/';
	}


	protected static function _getWikiConfigFile( $wiki ) {

		return self::_getWikiConfigPath() . $wiki . '.farmer';
	}

	public static function sanitizeName( $name ) {
		return strtolower( preg_replace( '/[^[:alnum:]]/', '', $name ) );
	}

	public static function sanitizeTitle( $title ) {
		//return preg_replace( '/[^[:alnum:]]/', '', $title );
	    return $title;
	}

	public function getUrl( $article = null ) {

	    $farmer = MediaWikiFarmer::getInstance();
	    $url =  str_replace("$1", $this->_name, $farmer->interwikiUrl) . '$1';

		if ( !is_null( $article ) ) {
			$url = str_replace( '$1', $article, $url );
		}
		return $url;
	}

	public function isDefaultWiki() {
		return $this->_name == MediaWikiFarmer::getInstance()->getDefaultWiki();
	}

	# ----------------
	# Permission stuff
	# ----------------

	public function setPermission( $group, $permission, $value ) {
		if ( !array_key_exists( $group, $this->_permissions ) ) {
			$this->_permissions[$group] = [];
		}

		$this->_permissions[$group][$permission] = $value ? true : false;
	}

	public function setPermissionForAll( $permission, $value ) {
		$this->setPermission( '*', $permission, $value );
	}

	public function setPermissionForUsers( $permission, $value ) {
		$this->setPermission( 'user', $permission, $value );
	}

	public function getPermission( $group, $permission ) {
		return isset(
			$this->_permissions[$group][$permission]
		) ? $this->_permissions[$group][$permission] : false;
	}

	public function getPermissionForAll( $permission ) {
		return $this->getPermission( '*', $permission );
	}

	public function getPermissionForUsers( $permission ) {
		return $this->getPermission( 'user', $permission );
	}

	public function userIsAdmin( $user ) {
		$adminGroup = '[farmer][' . $this->_name . '][admin]';

		return in_array( $adminGroup, $user->getGroups() );
	}

	# ---------------
	# Extension stuff
	# ---------------

	public function addExtension( MediaWikiFarmer_Extension $e ) {
		$this->_extensions[$e->name] = $e;
	}

	public function hasExtension( MediaWikiFarmer_Extension $e ) {
		return array_key_exists( $e->name, $this->_extensions );
	}

	# --------------
	# Database stuff
	# --------------

	/**
	 * Obtain a database connection suitable for interfacing with wiki $name
	 *
	 * @param bool $selectDB whether to select the database
	 * @return \Wikimedia\Rdbms\IDatabase
	 * @var string $wgDBprefix
	 */
	public function getDatabase( $selectDB = true ) {
	    global $wgDBserver, $wgDBtype, $wgDBprefix;
	    
		$farmer = MediaWikiFarmer::getInstance();
		if ( $selectDB ) {
			if ( isset( $this->_db ) && is_object( $this->_db ) ) {
				return $this->_db;
			}
			list( $db, $prefix ) = $farmer->splitWikiDB( $this->name );
			
			if ($this->_name != '')
			    $wgDBprefix= $this->_name . $farmer->dbTablePrefixSeparator;
		} else {
			$db = false;
			$prefix = '';
		}
		$user = $farmer->dbAdminUser;
		$password = $farmer->dbAdminPassword;
		$db = $farmer->databaseName;
		
		$object = Database::factory( $wgDBtype, [
				'host' => $wgDBserver,
				'user' => $user,
				'password' => $password,
				'dbname' => $db,
				'flags' => 0,
				'tablePrefix' => $prefix ] );

        $object->tablePrefix($prefix);
        
		if ( $selectDB ) {
			$this->_db = $object;
		}
		return $object;
	}

	/**
	 * Creates a new wiki in the database
	 *
	 * @todo Error check to make sure tables don't exist
	 */
	public function createDatabase() {
		$this->_createTablesForWiki();
		
		//TODO: can not createMainPage
		//$this->_createMainPageForWiki();
		
		$this->_populateInterwiki();
		$this->_populateUserGroups();
		
	}

	/**
	 * Creates the tables for a specified wiki
	 */
	protected function _createTablesForWiki() {

		global $wgSharedTables;

		// FIXME! Hacky
		$oldShared = $wgSharedTables;
		$wgSharedTables = [];

		$farmer = MediaWikiFarmer::getInstance();
		$db = false;
		try {
			$db = $this->getDatabase();
		} catch ( DBConnectionError $e ) {
			$db = false;
		}

		if ( !$db ) {
			list( $dbname, $prefix ) = $farmer->splitWikiDB( $this->name );
			$db = $this->getDatabase( false );
			$db->query( "CREATE DATABASE `{$dbname}`", __METHOD__ );
			$db->selectDB( $dbname );
		}

		$file = $farmer->dbSourceFile;
		$db->sourceFile( $file );

		$wgSharedTables = $oldShared;
	}

/*
	// @codingStandardsIgnoreStart
	protected function _createMainPageForWiki() {
	// @codingStandardsIgnoreEnd
		$db = $this->getDatabase();
		$db->tablePrefix($this->_name . '_');

		$titleobj = Title::newFromText( wfMessage(
			'mainpage'
		)->inContentLanguage()->useDatabase( false )->plain() );
		$article = new Article( $titleobj );
		$newid = $article->insertOn( $db );
		$revision = new Revision( [
			'page'	  => $newid,
			'text'	  => wfMessage( 'farmernewwikimainpage' )->inContentLanguage()->text(),
			'comment'   => '',
			'user'	  => 0,
			'user_text' => 'MediaWiki default',
		] );
		$revid = $revision->insertOn( $db );
		$article->updateRevisionOn( $db, $revision );

		// site_stats table entry
		$db->insert( 'site_stats', [
			'ss_row_id' => 1,
			'ss_total_views' => 0,
			'ss_total_edits' => 0,
			'ss_good_articles' => 0
		] );
	}
	*/

	protected function _createMainPageForWiki() {
	    //$db = wfGetDB(DB_MASTER);
	    //$db->tablePrefix($ihis->_name . '_');
	    
		//$status = Status::newGood();
		$title = Title::newMainPage();
		if ( $title->exists() ) {
			//$status->warning( 'config-install-mainpage-exists' );
			//return $status;
			return;
		}
		try {
			$page = WikiPage::factory( $title );
			$content = new WikitextContent(
			    wfMessage( 'farmernewwikimainpage' )->inContentLanguage()->text()
				//wfMessage( 'mainpagetext' )->inContentLanguage()->text() . "\n\n" .
				//wfMessage( 'mainpagedocfooter' )->inContentLanguage()->text()
			);

			$status = $page->doEditContent( $content,
				'',
				EDIT_NEW,
				false,
				User::newSystemUser( 'MediaWiki default' )
			);
		} catch ( Exception $e ) {
			// using raw, because $wgShowExceptionDetails can not be set yet
			//$status->fatal( 'config-install-mainpage-failed', $e->getMessage() );
		}

		//return $status;
	}

	/**
	 * Create interwiki
	 *
	 * @todo Finish implementing
	 */
	protected function _populateInterwiki() {

	    $farmer = MediaWikiFarmer::getInstance();
	    global $wgDBprefix;
	    $wgDBprefix = $this->pushDBprefix($this->defaultWiki . $farmer->dbTablePrefixSeparator);
	    
		$db = $this->getDatabase();
		$db->tablePrefix($wgDBprefix);
		
		$db->insert(
			'interwiki',
			[
			    'iw_prefix' => strtolower( $this->name ),
				'iw_url' => $this->getUrl(),
				'iw_local' => 1,
			],
			__METHOD__,
			[ 'IGNORE' ]
		);
		
		$wgDBprefix = $this->popDBprefix();
		$db->tablePrefix($wgDBprefix);
	}
	
	protected function _populateUserGroups() {
	    
	    global $wgFarmerSettings;
	    $farmer = MediaWikiFarmer::getInstance();
	    $Prefix1 = $wgFarmerSettings['defaultWiki']. $farmer->dbTablePrefixSeparator;
	    $Prefix2 = $this->_name . $farmer->dbTablePrefixSeparator;
	    
	    $db = $this->getDatabase();
	    
	    $query = "insert into `". $Prefix2. "user` (`user_name`,`user_real_name`, `user_password`,`user_newpassword`,`user_newpass_time`,`user_email`, `user_touched`,`user_token`,`user_email_authenticated`,`user_email_token`, `user_email_token_expires`,`user_registration`,`user_editcount`, `user_password_expires`) select `user_name`,`user_real_name`, `user_password`,`user_newpassword`,`user_newpass_time`,`user_email`, `user_touched`,`user_token`,`user_email_authenticated`,`user_email_token`, `user_email_token_expires`,`user_registration`,`user_editcount`, `user_password_expires` from `". $Prefix1. "user` where `user_name`='".$this->creator."'" ;
	    $db->query( $query, __METHOD__ );
	    
	    $query = "insert into `". $Prefix2. "user_groups` (`ug_user`,`ug_group`) select `user_id`,'sysop' from `". $Prefix2. "user` where `user_name`='".$this->creator."'" ;
        $db->query( $query, __METHOD__ );

	    $query = "insert into `". $Prefix2. "user_groups` (`ug_user`,`ug_group`) select `user_id`,'bureaucrat' from `". $Prefix2. "user` where `user_name`='".$this->creator."'" ;
        $db->query( $query, __METHOD__ );
        
	    $query = "insert into `". $Prefix2. "user_groups` (`ug_user`,`ug_group`) select `user_id`,'interface-admin' from `". $Prefix2. "user` where `user_name`='".$this->creator."'" ;
        $db->query( $query, __METHOD__ );
        
	    $query = "insert into `". $Prefix2. "actor` (`actor_user`, `actor_name`) select `user_id`,`user_name` from `". $Prefix2. "user` where `user_name`='".$this->creator."'" ;
        $db->query( $query, __METHOD__ );
	}

	public function deleteWiki() {
		$this->_deleteWikiTables();
		$this->_deleteWikiGroups();
		$this->_deleteInterWiki();
		$this->delete();
		MediaWikiFarmer::getInstance()->updateFarmList();
	}

	protected function _deleteWikiTables() {

		$db = $this->getDatabase();

		$result = $db->query( 'SHOW TABLES', __METHOD__ );

		$farmer = MediaWikiFarmer::getInstance();
		$prefix = $this->_name . $farmer->dbTablePrefixSeparator;

        global $wgFarmerSettings;
        $key = 'Tables_in_' .$wgFarmerSettings['databaseName'];
		foreach ( $result as $row ) {
		    $tbName = $row->$key;
			if ( $prefix == '' || strpos( $tbName, $prefix ) === 0 ) {
				$query = 'DROP TABLE `' . $tbName . '`';
				$db->query( $query, __METHOD__ );
			}
		}
	}


	protected function _deleteWikiGroups() {

		if ( MediaWikiFarmer::getInstance()->sharingGroups() ) {
			$db = $this->getDatabase();
			
			$query = 'DELETE FROM ' . $db->tableName( 'user_groups' ) . ' WHERE ug_group LIKE ';
			$query .= '\'[farmer][' . $this->_name . ']%\'';
			$db->query( $query, __METHOD__ );
		}
	}


	protected function _deleteInterwiki() {

		$db = $this->getDatabase();
		if ( $db->tableExists( 'interwiki' ) ) {
			$db->delete( 'interwiki', [ 'iw_prefix' => strtolower( $this->_name ) ], __METHOD__ );
		} else {
			wfDebug( __METHOD__ . ": Table 'interwiki' does not exists\n" );
		}
	}
}
