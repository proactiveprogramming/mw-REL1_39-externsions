<?php

namespace MediaWiki\WikispeechSpeechDataCollector\Crud;

/**
 * @file
 * @ingroup Extensions
 * @license GPL-2.0-or-later
 */

use MediaWiki\Revision\RevisionStore;
use MWException;
use User as MwUser;
use Wikimedia\Rdbms\ILoadBalancer;

/**
 * Information required to access underlying data stores used by the various CRUD implementations.
 *
 * @since 0.1.0
 */
class CrudContext {

	/**
	 * @see AbstractRdbmsCrud
	 * @var ILoadBalancer|null
	 */
	private $dbLoadBalancer;

	// @todo When bumping MW core version from 1.35, add TitleFactory and WikiPageFactory here,
	// @todo and replace the use of static methods in {@link AbstractMcrCrud}.

	/**
	 * @see AbstractMcrCrud
	 * @var MwUser|null
	 */
	private $mediawikiUser;

	/**
	 * @see AbstractMcrCrud
	 * @var RevisionStore|null
	 */
	private $revisionStore;

	/**
	 * @param ILoadBalancer|null $dbLoadBalancer
	 * @param MwUser|null $mediawikiUser
	 * @param RevisionStore|null $revisionStore
	 * @since 0.1.0
	 */
	public function __construct(
		?ILoadBalancer $dbLoadBalancer,
		?MwUser $mediawikiUser,
		?RevisionStore $revisionStore
	) {
		$this->dbLoadBalancer = $dbLoadBalancer;
		$this->mediawikiUser = $mediawikiUser;
		$this->revisionStore = $revisionStore;
	}

	/**
	 * @return ILoadBalancer
	 * @throws MWException If db load balancer not available in context.
	 * @since 0.1.0
	 */
	public function getDbLoadBalancer(): ILoadBalancer {
		if ( $this->dbLoadBalancer === null ) {
			throw new MWException( 'Db load balancer not available in context!' );
		}
		return $this->dbLoadBalancer;
	}

	/**
	 * @return MwUser
	 * @throws MWException If user not available in context.
	 * @since 0.1.0
	 */
	public function getMediawikiUser(): MwUser {
		if ( $this->mediawikiUser === null ) {
			throw new MWException( 'User not available in context!' );
		}
		return $this->mediawikiUser;
	}

	/**
	 * @return RevisionStore
	 * @throws MWException If revision store not available in context.
	 * @since 0.1.0
	 */
	public function getRevisionStore(): RevisionStore {
		if ( $this->revisionStore === null ) {
			throw new MWException( 'Revision store not available in context!' );
		}
		return $this->revisionStore;
	}

}
