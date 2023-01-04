<?php

namespace MediaWiki\WikispeechSpeechDataCollector\Crud\Mcr;

/**
 * @file
 * @ingroup Extensions
 * @license GPL-2.0-or-later
 */

use ExternalStoreException;
use MediaWiki\WikispeechSpeechDataCollector\Domain\Persistent;

/**
 * CRUD for composite association owner objects.
 *
 * These are objects that create their own unique identity. Compare to composite part objects
 * that inherit the identity of the composite owner object they are related to.
 *
 * A composite part object can not exist without the composite owner.
 *
 * E.g, {@link Recording} would be a composite owner object,
 * while {@link RecordingAnnotations} is a composite part object
 * that depends on an existing Recording in order to be created.
 *
 * @see AbstractCompositePartMcrCrud
 * @since 0.1.0
 */
abstract class AbstractCompositeOwnerMcrCrud extends AbstractMcrCrud {

	/**
	 * Asserts that identity is not set and that the page does not exist.
	 *
	 * @param Persistent $instance
	 * @throws ExternalStoreException
	 */
	public function create( Persistent $instance ): void {
		if ( $instance->getIdentity() ) {
			throw new ExternalStoreException( 'Identity already set' );
		}
		$this->getIdentityStrategy()->identityFactory( $instance );
		$page = $this->getIdentityStrategy()->getWikiPage(
			$instance->getIdentity(),
			$this->getContext()
		);
		if ( $page->exists() ) {
			// todo consider,
			// this probably means that we came up with an identity that was not unique.
			// should we rather just try again with a new identity?
			throw new ExternalStoreException( 'Page already exists' );
		}
		parent::create( $instance );
	}

}
