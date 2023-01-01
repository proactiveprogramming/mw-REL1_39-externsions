<?php
#
# Disclaimer: Following the discussion around the the future of magic links[1]
# (more specifically, its removal from mediawiki core), 'wsd' (untraced) 
# submitted a patch[2] to demonstrate how magiclinks could be turned into an extension.
# In the meantime, $wgEnableMagicLinks[3] was introduced to aid in phasing these out.
# 
# This extension is nothing but the extension.json wrapping of the simple submitted
# patch.
#
# [1] https://www.mediawiki.org/wiki/Requests_for_comment/Future_of_magic_links
# [2] https://phabricator.wikimedia.org/T28207#294990
# [3] https://www.mediawiki.org/wiki/Manual:$wgEnableMagicLinks
#
# 2018, Nuno Tavares <github.com/ntavares>


if ( function_exists( 'wfLoadExtension' ) ) {
	wfLoadExtension( 'MagicalLinkers' );
	/* wfWarn(
		'Deprecated PHP entry point used for MagicalLinkers extension. ' .
		'Please use wfLoadExtension instead, ' .
		'see https://www.mediawiki.org/wiki/Extension_registration for more details.'
	); */
	return true;
} else {
	die( 'The MagicalLinkers extension requires MediaWiki 1.25+' );
}


// NOTE: should we do something about $wgEnableMagicLinks?


$wgMagicalInit = false;
$wgMagicalPattern = array();

class MagicalLinkers {
    
	public static function parserBeforeTidyHook( &$parser, &$text ) {
        global $wgMagicalPattern;
        
		self::performInit();

        $text = preg_replace_callback(
			$wgMagicalPattern,
			'MagicalLinkers::replaceCallback',
			$text
		);

		return true;
	}

	public static function replaceCallback( $matches ) {
        global $wgMagicalLinkers;
        
		if( $matches[1] !== '' or $matches[2] !== '' ) {
			return $matches[0];
		}

		for( $x = 0; $x < count( $wgMagicalLinkers ); $x++ ) {
			if( $matches[$x + 1] !== '' ) {
                return call_user_func($wgMagicalLinkers[$x]['linker'], $matches[0]);
			}
		}

		return $matches[0];
	}

	public static function linkISBN( $text ) {
		$num = substr( $text, 5 );
		$num = strtr( $num, array(
			'x' => 'X',
			' ' => '',
			'-' => ''
		));
		$bsTitle = SpecialPage::getTitleFor( 'Booksources', $num );
		$safeUrl = $bsTitle->escapeLocalUrl();
		return '<a href=\'' . $safeUrl . '\' class=\'mw-magiclink-isbn\'>' . $text . '</a>';
	}

	public static function linkRFC( $text ) {
		$urlNumber = ltrim( substr( $text, 4 ), '0');
		$url = 'http://www.rfc-editor.org/rfc/rfc' . $urlNumber . '.txt';
		$safeUrl = $url;
		return '<a href=\'' . $safeUrl . '\' class=\'mw-magiclink-rfc\'>' . $text . '</a>';
	}

	public static function linkPMID( $text ) {
		$urlNumber = ltrim( substr( $text, 5 ), '0');
		$url = 'http://www.ncbi.nlm.nih.gov/pubmed/' . $urlNumber;
		$safeUrl = $url;
		return '<a href=\'' . $safeUrl . '\' class=\'mw-magiclink-pmid\'>' . $text . '</a>';
	}

/*
	public static function linkMMSI( $text ) {
		$num = substr( $text, 5 );
		$bsTitle = SpecialPage::getTitleFor( 'MMSIsources', $num );
		$safeUrl = $bsTitle->escapeLocalUrl();
		return '<a href=\'' . $safeUrl . '\' class=\'mw-magiclink-mmsi\'>' . $text . '</a>';
	}


	public static function linkIMO( $text ) {
		$num = substr( $text, 4 );
		$bsTitle = SpecialPage::getTitleFor( 'IMOsources', $num );
		$safeUrl = $bsTitle->escapeLocalUrl();
		return '<a href=\'' . $safeUrl . '\' class=\'mw-magiclink-imo\'>' . $text . '</a>';
	}

	public static function linkENI( $text ) {
		$num = substr( $text, 4 );
		$bsTitle = SpecialPage::getTitleFor( 'ENIsources', $num );
		$safeUrl = $bsTitle->escapeLocalUrl();
		return '<a href=\'' . $safeUrl . '\' class=\'mw-magiclink-eni\'>' . $text . '</a>';
	}
*/
	public static function createPattern() {
		global $wgMagicalLinkers;

		$safePatterns = array();

		foreach( $wgMagicalLinkers as $linker) {
			# ( without ?: would cause pattern capturing
			$unsafePattern = $linker['pattern'];
			$unsafePattern = str_replace( '\\(', '\\x28', $unsafePattern );
			$unsafePattern = str_replace( '(?:', '(', $unsafePattern ); # so we don't get (?:?:
			$safePatterns[] = '(' . str_replace( '(', '(?:', $unsafePattern) . ')';
		}

		$fullPattern = '/(?:' . implode(' | ', $safePatterns) . ')/x';

		return $fullPattern;
	}

	public static function performInit() {

		global $wgMagicalInit,
               $wgMagicalLinkers,
		       $wgMagicalClassic,
               $wgMagicalPattern;
        global $wgEnableMagicLinks;

		if( $wgMagicalInit ) {
			return;
		}

        # enforce disabling of mediawiki-core's logic for magiclinks,
        # we will take care of these from now one.
        $wgEnableMagicLinks = [ 
            'ISBN' => false, 
            'PMID' => false, 
            'RFC' => false 
        ];
        $_wgMagicalLinkers = array();
		
        # don't touch linked text
        $_wgMagicalLinkers[] = array(
            'linker' => '',
            'pattern' => '<a\\s.*?<\\/a>'
        );
        # don't touch tags
        $_wgMagicalLinkers[] = array(
            'linker' => '',
            'pattern' => '<.*?>'
        );

        // we want the user-extended ones to show at the bottom
        if( $wgMagicalClassic ) {
			$_wgMagicalLinkers[] = array(
				'linker' => 'MagicalLinkers::linkISBN',
				'pattern' => 'ISBN\\s(([0-9]-?){9,12}([0-9xX]))'
			);

			/*
            $wgMagicalLinkers[] = array(
				'linker' => 'MagicalLinkers::linkMMSI',
				'pattern' => 'MMSI\\s([0-9]+){9}'
			);

			$wgMagicalLinkers[] = array(
				'linker' => 'MagicalLinkers::linkIMO',
				'pattern' => 'IMO\\s([0-9]+){7}'
			);

			$wgMagicalLinkers[] = array(
				'linker' => 'MagicalLinkers::linkENI',
				'pattern' => 'ENI\\s([0-9]+){8}'
			);
            */

			$_wgMagicalLinkers[] = array(
				'linker' => 'MagicalLinkers::linkRFC',
				'pattern' => 'RFC\\s([0-9]+)'
			);

			$_wgMagicalLinkers[] = array(
				'linker' => 'MagicalLinkers::linkPMID',
				'pattern' => 'PMID\\s([0-9]+)'
			);
		}
        $wgMagicalLinkers = array_merge($_wgMagicalLinkers, $wgMagicalLinkers);
		$wgMagicalPattern = self::createPattern();

		$wgMagicalInit = true;
	}
}

