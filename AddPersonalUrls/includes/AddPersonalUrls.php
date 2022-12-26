<?php

/**
 * Code for the @ref Extensions-AddPersonalUrls.
 *
 * @file
 *
 * @ingroup Extensions
 * @ingroup Extensions-AddPersonalUrls
 *
 * @author [RV1971](https://www.mediawiki.org/wiki/User:RV1971)
 *
 */

/**
 * Class implementing the @ref Extensions-AddPersonalUrls.
 *
 * @ingroup Extensions-AddPersonalUrls
 */
class AddPersonalUrls {
	/* == public static methods == */

	/**
	 * Get an instance of this class.
	 *
	 * Due to the use of late static binding, the mechanism works for
	 * derived classes as well. All derived classes would use the same
	 * instance stored here, which is OK because we will never need
	 * more than one.
	 *
	 * @return AddPersonalUrls
	 */
	public static function &singleton() {
		static $instance;

		if ( !isset( $instance ) ) {
			$instance = new static;
		}

		return $instance;
	}

	/**
	 * Initialize this extension.
	 */
	public static function init() {
		global $wgHooks;

		$wgHooks['BeforePageDisplay'][] = self::singleton();
		$wgHooks['EditFormPreloadText'][] = self::singleton();
		$wgHooks['PersonalUrls'][] = self::singleton();
	}

	/* == hooks == */

	/**
	 * BeforePageDisplay hook handler
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/BeforePageDisplay
	 *
	 * Add the [Resource Modules]
	 * (https://www.mediawiki.org/wiki/$wgResourceModules) to the page.
	 *
	 * @param OutputPage &$out The OutputPage object.
	 *
	 * @param Skin &$skin Skin object that will be used to
	 * generate the page.
	 *
	 * @return bool Always TRUE.
	 */
	public function onBeforePageDisplay( OutputPage &$out, Skin &$skin ) {
		$out->addModules( 'ext.addPersonalUrls' );

		return true;
	}

	/**
	 * EditFormPreloadText hook handler
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/EditFormPreloadText
	 *
	 * Preload text when creating new pages in the User namespace.
	 * See @ref $wgAddPersonalUrlsTable for an explanation how the text is
	 * composed.
	 *
	 * @param string &$text Text to prefill edit form with.
	 *
	 * @param Title &$title Title of new page (Title Object).
	 *
	 * @return bool Always TRUE.
	 */
	public function onEditFormPreloadText( &$text, Title &$title ) {
		if ( $title->getNamespace() != NS_USER ) {
			return true;
		}

		/** Skip if there is already another preload text. */
		if ( $text ) {
			return true;
		}

		$msg1 = wfMessage( 'addpersonalurls-'
			. strtolower( $title->getSubpageText() ) . '-preload' );

		/** If the page-specific message does not exist, do not
		 *	preload anything.
		 */
		if ( !$msg1->exists() ) {
			return true;
		}

		$msg2 = wfMessage( 'addpersonalurls-preload' );

		$text = "<!-- {$msg1->text()}";

		if ( $msg1->text() !== '' && $msg2->text() !== '' ) {
			$text .= "\n\n";
		}

		$text .= "{$msg2->text()} -->";

		return true;
	}

	/**
	 * PersonalUrls hook handler
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/PersonalUrls
	 *
	 * This is the core of the extension which actually adds the URLs
	 * to the list of personal URLs.
	 *
	 * @param array &$personal_urls The array of URLs set up so far.
	 * @param Title $title The Title object of the current article.
	 * @param SkinTemplate $skin Skin template, for context
	 *
	 * @return bool Always TRUE.
	 */
	public function onPersonalUrls( array &$personal_urls, Title $title, SkinTemplate $skin ) {
		global $wgAddPersonalUrlsTable;

		$user = $skin->getUser();
		$username = $user->getName();

		/** Consider logged-in users only. */
		if ( $user->getID() ) {
			$pageurl = $title->getLocalURL();

			/** Extract link to user page in order to keep it as first
			 *	item.
			 */
			$urls = [ 'userpage' => array_shift( $personal_urls ) ];

			foreach ( $wgAddPersonalUrlsTable as $id => $url ) {
				/** Ignore items were the target is unset. This allows
				 * to remove in `LocalSettings.php` items defined in
				 * extension.json.
				 */
				if ( !isset( $url ) ) {
					continue;
				}

				/** Replace $username with actual username. */
				$url = str_replace( '$username', $username, $url );

				/** Setup URL details, distinguishing between internal
				 *	and external links.
				 */
				if ( strpos( $url, '://' ) !== false ) {
					$href = $url;
					$class = 'external text';
					$active = false;
				} else {
					/** Split the URL at '?', if any. */
					$components = explode( '?', $url, 2 );
					$name = $components[0];
					$urlaction = isset( $components[1] )
						? $components[1] : null;

					$linkedTitle = Title::newFromText( $name );
					if ( !$linkedTitle ) {
						continue;
					}
					$exists = $linkedTitle->getArticleID() != 0
						|| $linkedTitle->isSpecialPage();
					$href = $linkedTitle->getLocalURL( $urlaction );

					/** If a page does not exist and is not a special
					 *	page, open it for editing and format it as a
					 *	link to a new page.
					 */
					if ( !$exists ) {
						$href = $linkedTitle->getLocalURL( 'action=edit' );
						$class = 'new';
					} else {
						$class = null;
					}
					$active = $href == $pageurl;
				}

				$text = wfMessage( $id )->text();
				$urls[$id] = [
					'text' => $text,
					'href' => $href,
					'active' => $active,
					'class' => $class,
				];
			}

			/** Prepend new URLs to existing ones. */
			$personal_urls = $urls + $personal_urls;
		}

		return true;
	}
}
