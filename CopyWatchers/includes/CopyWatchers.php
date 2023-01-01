<?php
/**
 *
 * @addtogroup Extensions
 * @author James Montalvo
 * @copyright © 2014 by James Montalvo
 * @licence GNU GPL v3+
 */

namespace CopyWatchers;
use ParserFunctionHelper\ParserFunctionHelper;

class CopyWatchers extends ParserFunctionHelper {


	public function __construct ( \Parser &$parser ) {

		parent::__construct(
			$parser,
			'copywatchers',
			array( 'pages' => '', 'showoutput' => false ),
			array(  )
		);

	}

	public function render ( \Parser &$parser, $params ) {
		global $wgCanonicalNamespaceNames;

		$pagesToCopyArray = explode( ',', $params['pages'] );
		$showOutput = $params['showoutput'];


		$newWatchers = array();

		$output = "Copied watchers from:\n\n";

		foreach( $pagesToCopyArray as $page ) {

			$output .= "* $page";

			// returns Title object
			$titleObj = self::getNamespaceAndTitle( trim($page) );

			if ( $titleObj->isRedirect() ) {
				$redirectArticle = new \Article( $titleObj );

				$titleObj = $redirectArticle->getRedirectTarget();
				$output .= " (redirects to " . $titleObj->getFullText() . ")";
			}

			$ns_num = $titleObj->getNamespace();
			$title  = $titleObj->getDBkey();

			unset( $titleObj ); // prob not necessary since it will be reset shortly.

			$watchers = self::getPageWatchers( $ns_num, $title );
			$num_watchers = count($watchers);

			if ($num_watchers == 1)
				$output .= " (" . count($watchers) . " watcher)\n";
			else
				$output .= " (" . count($watchers) . " watchers)\n";

			foreach ( $watchers as $userID => $dummy ) {
				$newWatchers[$userID] = 0; // only care about $userID, and want unique.
			}

		}

		// add list of usernames as watchers to this Title
		foreach ($newWatchers as $userID => $dummy) {
			$u = \User::newFromId($userID);
			$u->addWatch( $parser->getTitle() );
		}

		if ( $showOutput )
			return $output;
		else
			return "";

	}

	static function getNamespaceAndTitle ( $pageName ) {

		// defaults
		$ns_num = NS_MAIN;
		$title = $pageName;

		$colonPosition = strpos( $pageName, ':' ); // location of colon if exists

		// this won't test for a leading colon...but shouldn't use parser function that way anyway...
		if ( $colonPosition ) {
			$test_ns = self::getNamespaceNumber(
				substr( $pageName, 0, $colonPosition )
			);

			// only reset $ns and $title if has colon, and pre-colon text actually is a namespace
			if ( $test_ns !== false ) {
				$ns_num = $test_ns;
				$title = substr( $pageName, $colonPosition+1 );
			}
		}

		return \Title::makeTitle( $ns_num, $title );
		//return (object)array("ns_num"=>$ns_num, "title"=>$title);

	}

	// returns number of namespace (can be zero) or false. Use ===.
	static function getNamespaceNumber ( $ns ) {
		global $wgCanonicalNamespaceNames;

		foreach ( $wgCanonicalNamespaceNames as $i => $text ) {
			if (preg_match("/$ns/i", $text)) {
				return $i;
			}
		}

		return false; // if $ns not found above, does not exist
	}

	static function getPageWatchers ($ns, $title) {

		// code adapted from Extension:WhoIsWatching
		$dbr = wfGetDB( DB_REPLICA );
		$watchingUserIDs = array();


		$res = $dbr->select(
			'watchlist',
			'wl_user',
			array('wl_namespace'=>$ns, 'wl_title'=>$title),
			__METHOD__
		);
		foreach ( $res as $row ) {
			$watchingUserIDs[ $row->wl_user ] = 0; // only care about the user ID, and want unique
		}

		return $watchingUserIDs;

	}

}
