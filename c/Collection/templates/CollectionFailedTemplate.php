<?php
/**
 * @defgroup Templates Templates
 * @file
 * @ingroup Templates
 */

namespace MediaWiki\Extension\Collection\Templates;

use MediaWiki\Extension\Collection\Session;
use QuickTemplate;
use Title;

/**
 * HTML template for Special:Book/rendering/ (failed)
 * @ingroup Templates
 */
class CollectionFailedTemplate extends QuickTemplate {
	public function execute() {
		echo wfMessage( 'coll-rendering_failed_text', $this->data['status'] )->parseAsBlock();

		$t = Title::newFromText( $this->data['return_to'] );
		if ( $t && $t->isKnown() ) {
			echo wfMessage( 'coll-return_to', $t )->parseAsBlock();
		}

		if ( Session::isEnabled() ) {
			$title_string = wfMessage( 'coll-failed_collection_info_text_article' )->inContentLanguage()->text();
		} else {
			$title_string = wfMessage( 'coll-failed_page_info_text_article' )->inContentLanguage()->text();
		}
		$t = Title::newFromText( $title_string );
		if ( $t && $t->exists() ) {
			echo $GLOBALS['wgOut']->parseAsContent( '{{:' . $t . '}}' );
		}
		?>

		<?php
	}
}
