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
 * CRUD for composite association part objects.
 *
 * These are objects that require an existing composite owner object
 * from which they inherit their identity.
 *
 * This means that there is a one-to-one relationship between all
 * composite owner and composite part objects. Thus an intermediate
 * relationship class is required in order to achieve a one-to-many
 * relationship between an owner and a part.
 *
 * E.g, {@link Recording} would be a composite owner object,
 * while {@link RecordingAnnotations} is a composite part object
 * that depends on an existing Recording in order to be created.
 * RecordingAnnotations contains a list of {@link RecordingAnnotation}
 * in order to create the one-to-many relationship between
 * Recording and RecordingAnnotation.
 *
 * @see AbstractCompositeOwnerMcrCrud
 * @since 0.1.0
 */
abstract class AbstractCompositePartMcrCrud extends AbstractMcrCrud {

	/**
	 * Asserts that identity is set.
	 *
	 * @param Persistent $instance
	 * @throws ExternalStoreException
	 */
	public function create( Persistent $instance ): void {
		if ( !$instance->getIdentity() ) {
			throw new ExternalStoreException( 'Identity not set' );
		}
		parent::create( $instance );
	}

}
