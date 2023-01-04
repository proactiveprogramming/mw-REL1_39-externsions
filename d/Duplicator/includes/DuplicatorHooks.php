<?php

class DuplicatorHooks {
	/**
	 * Build the link to be shown in the toolbox if appropriate
	 * @param Skin $skin
	 * @param array &$sidebar
	 */
	public static function efDuplicatorNavigation( Skin $skin, array &$sidebar ) {
		if ( !$skin->getTitle()->inNamespaces( [ NS_MAIN, NS_TALK ] ) ) {
			return;
		}

		$permissionManager = \MediaWiki\MediaWikiServices::getInstance()->getPermissionManager();
		if ( !$permissionManager->userHasRight( $skin->getUser(), 'duplicate' ) ) {
			return;
		}

		$toolbox = &$sidebar['TOOLBOX'];
		$insert = [
			'duplicator' => [
				'text' => $skin->msg( 'duplicator-toolbox' )->text(),
				'href' => $skin->makeSpecialUrl( 'Duplicator', 'source=' . wfUrlEncode( $skin->thispage ) ),
			]
		];

		if ( isset( $toolbox['permalink'] ) ) {
			$toolbox = wfArrayInsertAfter( $toolbox, $insert, 'permalink' );
		} else {
			$toolbox += $insert;
		}
	}

	/**
	 * Output the toolbox link if available
	 * @param BaseTemplate &$monobook
	 */
	public static function efDuplicatorToolbox( &$monobook ) {
		if ( isset( $monobook->data['nav_urls']['duplicator'] ) ) {
			if ( $monobook->data['nav_urls']['duplicator']['href'] == '' ) {
				$html = Html::element(
					'li',
					[ 'id' => 't-isduplicator' ],
					$monobook->getMsg( 'duplicator-toolbox' )->text()
				);
			} else {
				$html = Html::rawElement(
					'li',
					[ 'id' => 't-duplicator' ],
					Html::element(
						'a',
						[ 'href' => $monobook->data['nav_urls']['duplicator']['href'] ],
						$monobook->getMsg( 'duplicator-toolbox' )->text()
					)
				);
			}
			echo $html;
		}
	}
}
