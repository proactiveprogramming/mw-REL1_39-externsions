<?php
/**
 * sourceProtection
 *
 *
 * @license https://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 *
 */


class SourceProtection {

	/**
	 * Dive into the skin. Check if a user may edit. If not, remove tabs.
	 * @param SkinTemplate $sktemplate
	 * @param array $links
	 *
	 * @return bool
	 */
	public static function hideSource( SkinTemplate &$sktemplate, array &$links ) {
		// always remove viewsource tab
		$removeUs = array( 'viewsource' );
		foreach ( $removeUs as $view ) {
			if ( isset( $links['views'][ $view ] ) ) {
				unset( $links['views'][ $view ] );
			}
		}
		// grab user permissions
		if ( method_exists( $sktemplate, 'getTitle' ) ) {
			$title = $sktemplate->getTitle();
		} else {
			$title = $sktemplate->mTitle;
		}

		$user_can_edit = $title->userCan( 'edit' );

		//remove form_edit and history when edit is disabled
		if ( $user_can_edit === false ) {
			$rem = array( 'form_edit', 'history' );
			foreach ( $rem as $v ) {
				if ( isset( $links['views'][ $v ] ) ) {
					unset( $links['views'][ $v ] );
				}
			}
		}

		return true;

	}

	/**
	 * If a user has no edit rights, then make sure it is hard for them to view the source of a document
	 * @param $title
	 * @param $wgUser
	 * @param $action
	 * @param $result
	 *
	 * @return bool
	 */
	public static function disableActions( &$title, &$wgUser, $action, &$result ) {
		if ( in_array( 'edit', $wgUser->getRights(), true ) ) {
			return true;
		} else {
			// define the actions to be blocked
			$actionNotAllowed = array(
				'edit',
				'move',
				'history',
				'info',
				'raw',
				'delete',
				'revert',
				'revisiondelete',
				'rollback',
				'markpatrolled'
			);
			// Also disable the version difference options
			if ( isset( $_GET['diff'] ) ) {
				return false;
			}
			if ( isset( $_GET['action'] ) ) {
				$actie = $_GET['action'];
				if ( in_array( $actie, $actionNotAllowed ) ) {
					return false;
				}
			}

			// Any other action is fine
			return true;
		}
	}

	/**
	 * Prevent ShowReadOnly form to be shown. We should never get here anymore, but just in case.
	 * @param EditPage $editPage
	 * @param OutputPage $output
	 *
	 * @return OutputPage
	 */
	public static function doNotShowReadOnlyForm( EditPage $editPage, OutputPage $output ) {

		if ( method_exists( $editPage, 'getTitle' ) ) {
			$title = $editPage->getTitle();
		} else {
			$title = $editPage->mTitle;
		}
		$user_can_edit = $title->userCan( 'edit' );
		if ( ! $user_can_edit ) {
			$output->redirect( $editPage->getContextTitle() );
		}

		return $output;
	}

}
