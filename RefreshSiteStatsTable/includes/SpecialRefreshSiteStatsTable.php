<?php
/**
 * SpecialPage for RefreshSiteStatsTable extension
 *
 * Copyright © 2022 WikiMANNia <chef@wikimannia.org>
 * https://www.wikimannia.org/
 *
 * This program is free software.
 *
 * @file
 */

namespace RefreshSiteStatsTable;
use Title;

/**
* backward compatibility to MediaWiki v1.25 and v1.26
* fix an issue that was introduced here:
* https://github.com/WikiMANNia/mediawiki-extensions-HitCounters/commit/822140f6d96974f5051449837e7f46a771d5f6a5#diff-1b6cef982bd7ace2232d91536185b83a
* DB_REPLICA remains undefined in MediaWiki before v1.27
*/
defined('DB_REPLICA') or define('DB_REPLICA', DB_SLAVE);

use Html;
use SpecialPage;
use Xml;

class SpecialRefreshSiteStatsTable extends SpecialPage {

	private $mDBr;
	private $mDBw;
 	private $mPermission;
	private $mErrorClass;
	private $mSuccessClass;

	/**
	 * Constructor - sets up the new special page
	 */
	public function __construct() {
		parent::__construct( 'RefreshSiteStatsTable' );

		$this->mDBr = wfGetDB( DB_REPLICA );
		$this->mDBw = wfGetDB( DB_MASTER );
 		$this->mPermission = true;
 		$this->mErrorClass   = self::isBeforeVersion( '1.38' ) ? 'errorbox'   : 'mw-message-box-error';
 		$this->mSuccessClass = self::isBeforeVersion( '1.38' ) ? 'successbox' : 'mw-message-box-success';
	}

	public function doesWrites() {
		return true;
	}

	/**
	 * Different description will be shown on Special:SpecialPage depending on
	 * whether the user can modify the data.
	 * @return String
	 */
	public function getDescription() {
		return $this->msg( 'refreshsitestatstable-rights' )->plain();
	}

	/**
	 * Show the page to the user
	 *
	 * @param string|null $sub The subpage string argument (if any).
	 */
	public function execute( $sub ) {
		$output = $this->getOutput();
		$output->setPageTitle( $this->msg( 'refreshsitestatstable-title' ) );
		$output->addWikiMsg( 'refreshsitestatstable-intro' );

		$request = $this->getRequest();
		$this->setHeaders();

		$action = $sub ? $sub : $request->getVal( 'action', $sub );
		$return = $this->getPageTitle();

		if ( $action === 'submit' ) {
			$this->doSubmit();
		} else {
			$this->showForm( $action );
		}

		$output->returnToMain( false, Title::makeTitleSafe( NS_SPECIAL, 'Statistics' ) );
	}

	/**
	 * @param string $action The action of the form
	 */
	protected function showForm( $action ) {

		$request = $this->getRequest();
		$submit_action = 'submit';
		$button = 'ok';
		$status_OK = true;

		$topmessage   = $this->msg( 'refreshsitestatstable-title' )->text();
		$intromessage = '';
		$attrs_disabled = [ 'disabled' => 'disabled' ];
		$label = [ 'class' => 'mw-label' ];
		$input = [ 'class' => 'mw-input' ];
		$OK_msg    = '<span class="' . $this->mSuccessClass . '" style="display:inline; margin:0; padding:2px;">' . $this->msg( 'refreshsitestatstable-status-msg-ok'  )->text() . '</span>';
		$ERROR_msg = '<span class="' . $this->mErrorClass . '" style="display:inline; margin:0; padding:2px;">' . $this->msg( 'refreshsitestatstable-status-msg-error' )->text() . '</span>';
		$submit_button_attrs = [ 'tabindex' => 100, 'id' => 'mw-refreshsitestatstable-{$action}-submit' ];

		$good_articles_status_msg = $total_pages_status_msg = $images_status_msg = $users_status_msg = $OK_msg;
		$anzahl_counted_good  = (int)$this->mDBr->selectField( 'page', 'COUNT(*)', [ 'page_namespace' => NS_MAIN, 'page_is_redirect' => 0 ], __METHOD__ );
		$anzahl_stat_db_good  = (int)$this->mDBr->selectField( 'site_stats', 'ss_good_articles', [ 'ss_row_id' => 1 ], __METHOD__ );
		$anzahl_counted_total = (int)$this->mDBr->selectField( 'page', 'COUNT(*)', [], __METHOD__ );
		$anzahl_stat_db_total = (int)$this->mDBr->selectField( 'site_stats', 'ss_total_pages', [ 'ss_row_id' => 1 ], __METHOD__ );
		$anzahl_counted_images = (int)$this->mDBr->selectField( 'image', 'COUNT(*)', [], __METHOD__ );
		$anzahl_stat_db_images = (int)$this->mDBr->selectField( 'site_stats', 'ss_images', [ 'ss_row_id' => 1 ], __METHOD__ );
		$anzahl_counted_users = (int)$this->mDBr->selectField( 'user', 'COUNT(*)', [], __METHOD__ );
		$anzahl_stat_db_users = (int)$this->mDBr->selectField( 'site_stats', 'ss_users', [ 'ss_row_id' => 1 ], __METHOD__ );

		if ( $anzahl_counted_good !== $anzahl_stat_db_good ) {
			$status_OK = false;
			$good_articles_status_msg = $ERROR_msg;
		}
		if ( $anzahl_counted_total !== $anzahl_stat_db_total ) {
			$status_OK = false;
			$total_pages_status_msg = $ERROR_msg;
		}
		if ( $anzahl_counted_images !== $anzahl_stat_db_images ) {
			$status_OK = false;
			$images_status_msg = $ERROR_msg;
		}
		if ( $anzahl_counted_users !== $anzahl_stat_db_users ) {
			$status_OK = false;
			$users_status_msg = $ERROR_msg;
		}
		if ( $status_OK ) {
			$submit_button_attrs = array_merge( $submit_button_attrs, $attrs_disabled );
			$intromessage .= Html::rawElement( 'p', null, $this->msg( 'refreshsitestatstable-status-msg', $this->msg( 'refreshsitestatstable-status-msg-ok' )->text() )->text() );
		} else {
			$intromessage .= Html::rawElement( 'p', null, $this->msg( 'refreshsitestatstable-status-msg', $this->msg( 'refreshsitestatstable-status-msg-error' )->text() )->text() );
		}

		$formContent2 =
				Html::rawElement( 'tr', null,
					Html::rawElement( 'td', $label,
						'&nbsp;'
					) .
					Html::rawElement( 'td', $label,
						Xml::label( $this->msg( 'refreshsitestatstable-good-articles' )->text(), 'mw-refreshsitestatstable-good-articles'
						)
					) .
					Html::rawElement( 'td', $label,
						Xml::label( $this->msg( 'refreshsitestatstable-total-pages' )->text(), 'mw-refreshsitestatstable-total-pages'
						)
					) .
					Html::rawElement( 'td', $label,
						Xml::label( $this->msg( 'refreshsitestatstable-images' )->text(), 'mw-refreshsitestatstable-images'
						)
					) .
					Html::rawElement( 'td', $label,
						Xml::label( $this->msg( 'refreshsitestatstable-users' )->text(), 'mw-refreshsitestatstable-users'
						)
					)
				) .
				Html::rawElement( 'tr', null,
					Html::rawElement( 'td', $label,
						Xml::label( $this->msg( 'refreshsitestatstable-db-count' )->text(), 'mw-refreshsitestatstable-db-count'
						)
					) .
					Html::rawElement( 'td', $input,
						$anzahl_counted_good
					) .
					Html::rawElement( 'td', $input,
						$anzahl_counted_total
					) .
					Html::rawElement( 'td', $input,
						$anzahl_counted_images
					) .
					Html::rawElement( 'td', $input,
						$anzahl_counted_users
					)
				) .
				Html::rawElement( 'tr', null,
					Html::rawElement( 'td', $label,
						Xml::label( $this->msg( 'refreshsitestatstable-statistic' )->text(), 'mw-refreshsitestatstable-statistic'
						)
					) .
					Html::rawElement( 'td', $input,
						$anzahl_stat_db_good
					) .
					Html::rawElement( 'td', $input,
						$anzahl_stat_db_total
					) .
					Html::rawElement( 'td', $input,
						$anzahl_stat_db_images
					) .
					Html::rawElement( 'td', $input,
						$anzahl_stat_db_users
					)
				) .
				Html::rawElement( 'tr', null,
					Html::rawElement( 'td', $label,
						Xml::label( $this->msg( 'refreshsitestatstable-status' )->text(), 'mw-refreshsitestatstable-status'
						)
					) .
					Html::rawElement( 'td', $input,
						$good_articles_status_msg
					) .
					Html::rawElement( 'td', $input,
						$total_pages_status_msg
					) .
					Html::rawElement( 'td', $input,
						$images_status_msg
					) .
					Html::rawElement( 'td', $input,
						$users_status_msg
					)
				);
		$form =
			Xml::fieldset( $topmessage,
				Html::rawElement(
					'form', [
						'id' => 'mw-refreshsitestatstable-{$action}form',
						'method' => 'post',
						'action' => $this->getPageTitle()->getLocalURL( [ 'action' => $submit_action ] )
					],
					Html::rawElement( 'p', null, $intromessage ) .
					Html::rawElement( 'table', [ 'id' => 'mw-refreshsitestatstable-{$action}' ],
						$formContent2 .
						Html::rawElement( 'tr', null,
							Html::rawElement( 'td', null, '' ) .
							Html::rawElement( 'td', [ 'class' => 'mw-submit' ],
								Xml::submitButton( $this->msg( $button )->text(),
									$submit_button_attrs
								)
							)
						) .
						Html::hidden( 'wpEditToken', $this->getUser()->getEditToken() ) .
						Html::hidden( 'wpActionToken', $action )
					)
				)
			);

		$this->getOutput()->addHTML( $form );

		return;
	}

	protected function doSubmit() {

		$request = $this->getRequest();
		$do = $request->getVal( 'wpActionToken' );
		$selfTitle = $this->getPageTitle();
		$status_OK = true;

		$anzahl_counted  = (int)$this->mDBr->selectField( 'page', 'COUNT(*)', [ 'page_namespace' => NS_MAIN, 'page_is_redirect' => 0 ], __METHOD__ );
		$anzahl_stat_old = (int)$this->mDBr->selectField( 'site_stats', 'ss_good_articles', [ 'ss_row_id' => 1 ], __METHOD__ );

		if ( $anzahl_counted === $anzahl_stat_old ) {
			$this->success( 'refreshsitestatstable-status-ok-good', $anzahl_counted );
		} else {
			$this->mDBw->update( 'site_stats', [ 'ss_good_articles' => $anzahl_counted ], [ 'ss_row_id' => 1 ], __METHOD__, 'IGNORE' );
			$anzahl_stat_new = (int)$this->mDBr->selectField( 'site_stats', 'ss_good_articles', [ 'ss_row_id' => 1 ], __METHOD__ );
			if ( $anzahl_counted === $anzahl_stat_new ) {
				$this->success( 'refreshsitestatstable-inconsistent-good', $anzahl_stat_old, $anzahl_stat_new );
			} else {
				$status_OK = false;
			}
		}

		$anzahl_counted  = (int)$this->mDBr->selectField( 'page', 'COUNT(*)', [], __METHOD__ );
		$anzahl_stat_old = (int)$this->mDBr->selectField( 'site_stats', 'ss_total_pages', [ 'ss_row_id' => 1 ], __METHOD__ );

		if ( $anzahl_counted === $anzahl_stat_old ) {
			$this->success( 'refreshsitestatstable-status-ok-total', $anzahl_counted );
		} else {
			$this->mDBw->update( 'site_stats', [ 'ss_total_pages' => $anzahl_counted ], [ 'ss_row_id' => 1 ], __METHOD__, 'IGNORE' );
			$anzahl_stat_new = (int)$this->mDBr->selectField( 'site_stats', 'ss_total_pages', [ 'ss_row_id' => 1 ], __METHOD__ );
			if ( $anzahl_counted === $anzahl_stat_new ) {
				$this->success( 'refreshsitestatstable-inconsistent-total', $anzahl_stat_old, $anzahl_stat_new );
			} else {
				$status_OK = false;
			}
		}

		$anzahl_counted  = (int)$this->mDBr->selectField( 'image', 'COUNT(*)', [], __METHOD__ );
		$anzahl_stat_old = (int)$this->mDBr->selectField( 'site_stats', 'ss_images', [ 'ss_row_id' => 1 ], __METHOD__ );

		if ( $anzahl_counted === $anzahl_stat_old ) {
			$this->success( 'refreshsitestatstable-status-ok-images', $anzahl_counted );
		} else {
			$this->mDBw->update( 'site_stats', [ 'ss_images' => $anzahl_counted ], [ 'ss_row_id' => 1 ], __METHOD__, 'IGNORE' );
			$anzahl_stat_new = (int)$this->mDBr->selectField( 'site_stats', 'ss_images', [ 'ss_row_id' => 1 ], __METHOD__ );
			if ( $anzahl_counted === $anzahl_stat_new ) {
				$this->success( 'refreshsitestatstable-inconsistent-images', $anzahl_stat_old, $anzahl_stat_new );
			} else {
				$status_OK = false;
			}
		}

		$anzahl_counted  = (int)$this->mDBr->selectField( 'user', 'COUNT(*)', [], __METHOD__ );
		$anzahl_stat_old = (int)$this->mDBr->selectField( 'site_stats', 'ss_users', [ 'ss_row_id' => 1 ], __METHOD__ );

		if ( $anzahl_counted === $anzahl_stat_old ) {
			$this->success( 'refreshsitestatstable-status-ok-users', $anzahl_counted );
		} else {
			$this->mDBw->update( 'site_stats', [ 'ss_users' => $anzahl_counted ], [ 'ss_row_id' => 1 ], __METHOD__, 'IGNORE' );
			$anzahl_stat_new = (int)$this->mDBr->selectField( 'site_stats', 'ss_users', [ 'ss_row_id' => 1 ], __METHOD__ );
			if ( $anzahl_counted === $anzahl_stat_new ) {
				$this->success( 'refreshsitestatstable-inconsistent-users', $anzahl_stat_old, $anzahl_stat_new );
			} else {
				$status_OK = false;
			}
		}

		if ( !$status_OK ) {
			$this->error( 'refreshsitestatstable-status-error' );
			$this->showForm( $do );
		}

		return;
	}

	/**
	 * Output error message stuff :)
	 */
	private function error() {
		$args = func_get_args();
		$this->getOutput()->wrapWikiMsg( '<p class="' . $this->mErrorClass . '" style="display:block;">$1</p>', $args );
	}

	/**
	 * Output success message stuff :)
	 */
	private function success() {
		$args = func_get_args();
		$this->getOutput()->wrapWikiMsg( '<div class="' . $this->mSuccessClass . '">$1</div><br style="clear:both" />', $args );
	}

	/**
	 * string $version
	 * return bool
	 */
	private static function isBeforeVersion( $version = '1.38' ) {
		global $wgVersion;

		return version_compare( $wgVersion, $version, '<' );
	}

	protected function getGroupName() {
		return 'wiki';
	}
}
