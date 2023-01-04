<?php
// phpcs below is used to disable function name on L#20
// phpcs:disable MediaWiki.NamingConventions.LowerCamelFunctionsName.FunctionName

namespace MediaWiki\Extension\SaveSpinner;

use EditPage;
use MediaWiki\Hook\EditPage__showEditForm_initialHook;
use OutputPage;

/**
 * @license MIT
 */
class SaveSpinnerHooks implements EditPage__showEditForm_initialHook {
	/**
	 * @param EditPage $editPage
	 * @param OutputPage $output
	 */
	public function onEditPage__showEditForm_initial( $editPage, $output ) {
		$output->addModules( 'ext.savespinner.main' );
	}

	/**
	 * @param string $form_text
	 */
	public static function onRenderingEnd( $form_text ) {
		global $wgOut;
		$wgOut->addModules( 'ext.savespinner.main' );
	}
}
