<?php

/**
* Main class for GetPageInfo MediaWiki extension
*/

class GetPageInfo {

	public static function executeGetTitle( $parser, $frame, $args ) {

		$fulltitle = "";

		if ( isset( $args[0] ) ) {
			$fulltitle = trim( $frame->expand( $args[0] ) );
		}	
	
		$extra = "";
	
		if (isset( $args[1] ) ) {
			$extra = trim( $frame->expand( $args[1] ) );
		}

		$title = "";
	
		if ( is_object( Title::newFromText( $fulltitle ) ) ) {
	
			$title = Title::newFromText( $fulltitle )->getText();

		}

		if ( isset( $args[3]) ) {

			$show = trim( $frame->expand( $args[3] ) );
			if ( empty( $show ) ) {
				$show = $title;
			}
		} else {
			$show = $title;
		}

		if ( $extra == 'link' ) {
	
			if ( isset( $args[2]) ) {
				global $wgServer;
				global $wgScript;

				$revid =  trim( $frame->expand( $args[2] ) )  ;
				if ( empty( $revid ) ) {
					$title = "[[$fulltitle|$show]]";
				} else {
					$urlfulltitle = urlencode( $fulltitle );
					$title = "[".$wgServer.$wgScript."?title=".$urlfulltitle."&oldid=".$revid." ".$show."]";
				}
			} else {
				$title = "[[$fulltitle|$show]]";
			}
		}

		return( $title );
	}

	public static function executeGetNS( $parser, $frame, $args ) {

		$fulltitle = trim( $frame->expand( $args[0] ) );
	
		$ns = "";
	
		if ( is_object( Title::newFromText( $fulltitle ) ) ) {
	
			$ns = Title::newFromText( $fulltitle )->getNsText();
		}

		return( $ns );
	
	}

	public static function executeGetSummary( $parser, $frame, $args ) {

		$revid =  trim( $frame->expand( $args[0] ) );
		$summary = "";
	
	
		if ( is_numeric( $revid ) ) {
	
			if ( is_object( Revision::newFromId( $revid ) ) ) {
		
				$revobj = Revision::newFromId( $revid );
				$summary = $revobj->getComment();
			}
	
		}
	
		return( $summary );

	}

}

