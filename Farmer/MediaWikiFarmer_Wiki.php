<?php

use MediaWiki\MediaWikiServices;
use Wikimedia\Rdbms\IMaintainableDatabase;

/**
 * Represents a configuration for a specific wiki
 * Created on Jul 20, 2006
 *
 * @author Gregory Szorc <gregory.szorc@gmail.com>
 */
class MediaWikiFarmer_Wiki {

	/** @var string Name of wiki */
	private $name;

	/** @var string */
	private $title;

	/** @var string */
	private $description;

	/** @var string Username of person who created wiki */
	private $creator;

	/** @var MediaWikiFarmer_Extension[] Extensions to load for this wiki */
	private $extensions = [];

	/** @var array Global variables set for this wiki */
	private $variables = [];

	/** @var array[] Permissions are so funky, we give them their own variable */
	private $permissions = [ '*' => [], 'user' => [] ];

	/** @var IMaintainableDatabase */
	private $db;

	/**
	 * Creates a wiki instance from a wiki name
	 * @param string $wiki
	 * @param array $variables
	 */
	public function __construct( $wiki, $variables = [] ) {
		$this->name = $wiki;
		$this->variables = $variables;
	}

	/**
	 * @param string $key
	 * @return mixed
	 */
	public function __get( $key ) {
		if ( substr( $key, 0, 2 ) === 'wg' ) {
			return $this->variables[$key] ?? null;
		}

		return $this->$key ?? null;
	}

	/**
	 * @param string $k
	 * @param mixed $v
	 */
	public function __set( $k, $v ) {
		if ( substr( $k, 0, 2 ) === 'wg' ) {
			$this->variables[$k] = $v;
		} elseif ( property_exists( $this, $k ) ) {
			$this->$k = $v;
		}
	}

	/**
	 * How to represent this object as a string
	 * @return string
	 */
	public function __toString() {
		return $this->name;
	}

	/**
	 * @param string $wiki
	 * @param array $variables
	 * @return self
	 */
	public static function factory( $wiki, $variables = [] ) {
		$farmer = MediaWikiFarmer::getInstance();

		if ( $farmer->useDatabase() ) {
			$dbr = $farmer->getDB( DB_REPLICA );
			$row = $dbr->selectRow( 'farmer_wiki', '*', [ 'fw_name' => $wiki ], __METHOD__ );
			if ( $row === false ) {
				return new self( $wiki, $variables );
			} else {
				return self::newFromRow( $row );
			}
		} else {
			$file = self::getWikiConfigFile( $wiki );

			if ( is_readable( $file ) ) {
				$content = file_get_contents( $file );
				$obj = unserialize( $content );
				if ( $obj instanceof self ) {
					return $obj;
				} else {
					throw new MWException( 'Stored wiki is corrupt.' );
				}
			} else {
				return new self( $wiki, $variables );
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

	/**
	 * @param stdClass $row
	 * @return self
	 */
	public static function newFromRow( $row ) {
		$wiki = new self( $row->fw_name );
		$wiki->title = $row->fw_title;
		$wiki->description = $row->fw_description;
		$wiki->creator = $row->fw_creator;
		$wiki->variables = unserialize( $row->fw_parameters );
		$wiki->permissions = unserialize( $row->fw_permissions );

		$dbr = MediaWikiFarmer::getInstance()->getDB( DB_REPLICA );
		$res = $dbr->select(
			[ 'farmer_extension', 'farmer_wiki_extension' ],
			'*',
			[ 'fwe_wiki' => $row->fw_id ],
			__METHOD__,
			[],
			[ 'farmer_wiki_extension' => [ 'LEFT JOIN', 'fwe_extension = fe_id' ] ]
		);
		$wiki->extensions = [];
		foreach ( $res as $row ) {
			$wiki->extensions[$row->fe_name] = MediaWikiFarmer_Extension::newFromRow( $row );
		}

		return $wiki;
	}

	public function create() {
		$farmer = MediaWikiFarmer::getInstance();

		// save the database prefix accordingly
		$this->wgDefaultSkin = $farmer->defaultSkin;

		// before we create the database, make sure this database doesn't really exist yet
		if ( !$this->exists() && !$this->databaseExists() ) {
			$this->save();
			$this->createDatabase();
			$farmer->updateFarmList();
		} else {
			throw new MWException(
				wfMessage( 'farmer-error-exists' )->rawParams( $this->name )->escaped()
			);
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
					'farmer_wiki', 1, [ 'fw_name' => $this->name ], __METHOD__
				);
		} else {
			return file_exists( self::getWikiConfigFile( $this->name ) );
		}
	}

	public function save() {
		$farmer = MediaWikiFarmer::getInstance();

		if ( $farmer->useDatabase() ) {
			$dbw = $farmer->getDB( DB_MASTER );
			$new = [
				'fw_name' => $this->name,
				'fw_title' => $this->title,
				'fw_description' => $this->description,
				'fw_creator' => $this->creator,
				'fw_parameters' => serialize( $this->variables ),
				'fw_permissions' => serialize( $this->permissions ),
			];

			$curId = $dbw->selectField( 'farmer_wiki', 'fw_id', [
					'fw_name' => $this->name ], __METHOD__
			);
			if ( $curId == null ) {
				$dbw->insert( 'farmer_wiki', $new, __METHOD__ );
				$curId = $dbw->insertId();
			} else {
				$dbw->update( 'farmer_wiki', $new, [ 'fw_id' => $curId ], __METHOD__ );
			}

			$insert = [];
			foreach ( $this->extensions as $ext ) {
				$insert[] = [ 'fwe_wiki' => $curId, 'fwe_extension' => $ext->id ];
			}
			$dbw->delete( 'farmer_wiki_extension', [ 'fwe_wiki' => $curId ], __METHOD__ );
			$dbw->insert( 'farmer_wiki_extension', $insert, __METHOD__ );

			return true;
		} else {
			$content = serialize( $this );
			return ( file_put_contents(
					self::getWikiConfigFile( $this->name ), $content, LOCK_EX
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
					'fw_name' => $this->name
				], __METHOD__
			);
			$dbw->delete( 'farmer_wiki', [ 'fw_name' => $this->name ], __METHOD__ );
		} else {
			unlink( self::getWikiConfigFile( $this->name ) );
		}
	}

	/**
	 * @return bool
	 */
	public function databaseExists() {
		try {
			$db = $this->getDatabase();
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
		// loop over defined variables and set them in the global scope
		foreach ( $this->variables as $k => $v ) {
			$GLOBALS[$k] = $v;
		}

		// we need to bring some global variables into scope so we can load extensions properly
		// phpcs:disable MediaWiki.VariableAnalysis.MisleadingGlobalNames
		// phpcs:ignore MediaWiki.Usage.ForbiddenFunctions
		extract( $GLOBALS, EXTR_REFS );

		// register all the extensions
		foreach ( $this->extensions as $extension ) {
			foreach ( $extension->includeFiles as $file ) {
				require_once $file;
			}
		}

		$farmer = MediaWikiFarmer::getInstance();
		if ( $farmer->useWgConf() ) {
			// Nothing for now
		} else {
			$wgSitename = $this->title;

			// We initialize the per-wiki storage root and all related global variables
			$wikiDir = $farmer->getStorageRoot() . $this->name . '/';
			$wikiPath = $farmer->getStorageUrl() . $this->name . '/';

			$wgUploadDirectory = $wikiDir . 'uploads';
			$wgMathDirectory = $wikiDir . 'math';
			$wgTmpDirectory = $wikiDir . 'tmp';

			$wgUploadPath = $wikiPath . 'uploads';
			$wgMathPath = $wikiPath . 'math';
			$wgTmpPath = $wikiPath . 'tmp';

			// DB settings
			list( $wgDBname, $wgDBprefix ) = $farmer->splitWikiDB( $this->name );
		}

		// we allocate permissions to the necessary groups

		foreach ( $this->permissions['*'] as $k => $v ) {
			$wgGroupPermissions['*'][$k] = $v;
		}

		foreach ( $this->permissions['user'] as $k => $v ) {
			$wgGroupPermissions['user'][$k] = $v;
		}

		$wgGroupPermissions['sysop']['read'] = true;

		// assign permissions to administrators of this wiki
		if ( $farmer->sharingGroups() ) {
			$group = '[farmer][' . $this->name . '][admin]';

			$grantToWikiAdmins = [ 'read', 'edit' ];

			foreach ( $grantToWikiAdmins as $v ) {
				$wgGroupPermissions[$group][$v] = true;
			}
		}
		// phpcs:enable

		$callback = $farmer->initCallback();
		if ( $callback ) {
			if ( is_callable( $callback ) ) {
				call_user_func( $callback, $this );
			} else {
				trigger_error( '$wgFarmerSettings[\'initCallback\'] is not callable', E_USER_WARNING );
			}
		}
	}

	/**
	 * @return string
	 */
	private static function getWikiConfigPath() {
		$farmer = MediaWikiFarmer::getInstance();
		return $farmer->getConfigPath() . '/wikis/';
	}

	/**
	 * @param string $wiki
	 * @return string
	 */
	private static function getWikiConfigFile( $wiki ) {
		return self::getWikiConfigPath() . $wiki . '.farmer';
	}

	/**
	 * @param string $name
	 * @return string
	 */
	public static function sanitizeName( $name ) {
		return strtolower( preg_replace( '/[^[:alnum:]]/', '', $name ) );
	}

	/**
	 * @param string $title
	 * @return string
	 */
	public static function sanitizeTitle( $title ) {
		return preg_replace( '/[^[:alnum:]]/', '', $title );
	}

	/**
	 * @param string|null $article
	 * @return string
	 */
	public function getUrl( $article = null ) {
		if ( MediaWikiFarmer::getInstance()->useWgConf() ) {
			global $wgConf;
			$server = $wgConf->get( 'wgServer', $this->name );
			$articlePath = $wgConf->get( 'wgArticlePath', $this->name );
			if ( !$articlePath ) {
				$usePathInfo = $wgConf->get( 'wgUsePathInfo', $this->name );
				if ( $usePathInfo === null ) {
					global $wgUsePathInfo;
					$usePathInfo = $wgUsePathInfo;
				}
				$articlePath = $wgConf->get( 'wgScriptPath', $this->name ) .
					( $usePathInfo ? '/$1' : '?title=$1' );
			}
			$url = $server . $articlePath;
		} else {
			$url = wfMessage( 'farmerinterwikiurl' )->rawParams(
				$this->name, '$1'
			)->inContentLanguage()->text();
		}
		if ( $article !== null ) {
			$url = str_replace( '$1', $article, $url );
		}
		return $url;
	}

	/**
	 * @return bool
	 */
	public function isDefaultWiki() {
		return $this->name == MediaWikiFarmer::getInstance()->getDefaultWiki();
	}

	# ----------------
	# Permission stuff
	# ----------------

	/**
	 * @param string $group
	 * @param string $permission
	 * @param bool $value
	 */
	public function setPermission( $group, $permission, $value ) {
		if ( !array_key_exists( $group, $this->permissions ) ) {
			$this->permissions[$group] = [];
		}

		$this->permissions[$group][$permission] = $value ? true : false;
	}

	/**
	 * @param string $permission
	 * @param bool $value
	 */
	public function setPermissionForAll( $permission, $value ) {
		$this->setPermission( '*', $permission, $value );
	}

	/**
	 * @param string $permission
	 * @param bool $value
	 */
	public function setPermissionForUsers( $permission, $value ) {
		$this->setPermission( 'user', $permission, $value );
	}

	/**
	 * @param string $group
	 * @param string $permission
	 * @return bool
	 */
	public function getPermission( $group, $permission ) {
		return $this->permissions[$group][$permission] ?? false;
	}

	/**
	 * @param string $permission
	 * @return bool
	 */
	public function getPermissionForAll( $permission ) {
		return $this->getPermission( '*', $permission );
	}

	/**
	 * @param string $permission
	 * @return bool
	 */
	public function getPermissionForUsers( $permission ) {
		return $this->getPermission( 'user', $permission );
	}

	/**
	 * @param User $user
	 * @return bool
	 */
	public function userIsAdmin( $user ) {
		$adminGroup = '[farmer][' . $this->name . '][admin]';

		if ( method_exists( MediaWikiServices::class, 'getUserGroupManager' ) ) {
			// MW 1.35+
			$groups = MediaWikiServices::getInstance()->getUserGroupManager()->getUserGroups( $user );
		} else {
			$groups = $user->getGroups();
		}
		return in_array( $adminGroup, $groups );
	}

	# ---------------
	# Extension stuff
	# ---------------

	/**
	 * @param MediaWikiFarmer_Extension $e
	 */
	public function addExtension( MediaWikiFarmer_Extension $e ) {
		$this->extensions[$e->name] = $e;
	}

	/**
	 * @param MediaWikiFarmer_Extension $e
	 * @return bool
	 */
	public function hasExtension( MediaWikiFarmer_Extension $e ) {
		return array_key_exists( $e->name, $this->extensions );
	}

	# --------------
	# Database stuff
	# --------------

	/**
	 * Obtain a database connection suitable for interfacing with wiki $name
	 *
	 * @param bool $selectDB whether to select the database
	 * @return IMaintainableDatabase
	 */
	public function getDatabase( $selectDB = true ) {
		global $wgDBserver, $wgDBtype;
		$farmer = MediaWikiFarmer::getInstance();
		if ( $selectDB ) {
			if ( isset( $this->db ) && is_object( $this->db ) ) {
				return $this->db;
			}
			list( $db, $prefix ) = $farmer->splitWikiDB( $this->name );
		} else {
			$db = false;
			$prefix = '';
		}
		$user = $farmer->dbAdminUser;
		$password = $farmer->dbAdminPassword;
		$class = 'Database' . ucfirst( $wgDBtype );
		$object = new $class( $wgDBserver, $user, $password, $db, 0, $prefix );
		if ( $selectDB ) {
			$this->db = $object;
		}
		return $object;
	}

	/**
	 * Creates a new wiki in the database
	 *
	 * @todo Error check to make sure tables don't exist
	 */
	public function createDatabase() {
		$this->createTablesForWiki();
		$this->createMainPageForWiki();
		$this->populateInterwiki();
		$this->populateUserGroups();
	}

	/**
	 * Creates the tables for a specified wiki
	 */
	private function createTablesForWiki() {
		global $wgSharedTables;

		// FIXME! Hacky
		$oldShared = $wgSharedTables;
		$wgSharedTables = [];

		$farmer = MediaWikiFarmer::getInstance();
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

	private function createMainPageForWiki() {
		$db = $this->getDatabase();

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

	/**
	 * Create interwiki
	 *
	 * @todo Finish implementing
	 */
	private function populateInterwiki() {
		$db = $this->getDatabase();
		$db->insert(
			'interwiki',
			[
				'iw_prefix' => strtolower( $this->title ),
				'iw_url' => $this->getUrl(),
				'iw_local' => 1,
			],
			__METHOD__,
			[ 'IGNORE' ]
		);
	}

	private function populateUserGroups() {
		if ( $this->creator ) {
			if ( MediaWikiFarmer::getInstance()->sharingGroups() ) {
				$user = User::newFromname( $this->creator );
				$group = '[farmer][' . $this->name . '][admin]';
				if ( method_exists( MediaWikiServices::class, 'getUserGroupManager' ) ) {
					// MW 1.35+
					MediaWikiServices::getInstance()->getUserGroupManager()->addUserToGroup( $user, $group );
				} else {
					$user->addGroup( $group );
				}
			} else {
				if ( method_exists( MediaWikiServices::class, 'getUserIdentityLookup' ) ) {
					// MW 1.36+
					$userIdentity = MediaWikiServices::getInstance()->getUserIdentityLookup()
						->getUserIdentityByName( $this->creator );
					$userId = $userIdentity ? $userIdentity->getId() : null;
				} else {
					$userId = User::idFromName( $this->creator );
				}
				if ( $userId ) {
					$insert = [
						[ 'ug_user' => $userId, 'ug_group' => 'sysop' ],
						[ 'ug_user' => $userId, 'ug_group' => 'bureaucrat' ],
					];
					$db = $this->getDatabase();
					$db->insert( 'user_groups', $insert, __METHOD__ );
				}
			}
		}
	}

	public function deleteWiki() {
		$this->deleteWikiTables();
		$this->deleteWikiGroups();
		$this->deleteInterwiki();
		$this->delete();
		MediaWikiFarmer::getInstance()->updateFarmList();
	}

	private function deleteWikiTables() {
		$db = $this->getDatabase();
		$result = $db->query( 'SHOW TABLES', __METHOD__ );

		$prefix = $db->getProperty( 'mTablePrefix' );

		foreach ( $result as $row ) {
			if ( $prefix == '' || strpos( $row[0], $prefix ) === 0 ) {
				$query = 'DROP TABLE `' . $row[0] . '`';
				$db->query( $query, __METHOD__ );
			}
		}
	}

	private function deleteWikiGroups() {
		if ( MediaWikiFarmer::getInstance()->sharingGroups() ) {
			$db = $this->getDatabase();
			$query = 'DELETE FROM ' . $db->tableName( 'user_groups' ) . ' WHERE ug_group LIKE ';
			$query .= '\'[farmer][' . $this->name . ']%\'';
			$db->query( $query, __METHOD__ );
		}
	}

	private function deleteInterwiki() {
		$db = $this->getDatabase();
		if ( $db->tableExists( 'interwiki' ) ) {
			$db->delete( 'interwiki', [ 'iw_prefix' => strtolower( $this->title ) ], __METHOD__ );
		} else {
			wfDebug( __METHOD__ . ": Table 'interwiki' does not exists\n" );
		}
	}
}
