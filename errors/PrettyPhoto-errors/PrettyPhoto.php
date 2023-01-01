<?php
if ( !defined( 'MEDIAWIKI' ) ) {
	die( "This file is part of the PrettyPhoto extension. It is not a valid entry point.\n" );
}

$wgExtensionCredits['other'][] = array(
    'path' => __FILE__,
    'name' => 'PrettyPhoto',
    'author' => 'Igor Zolotykh, Dmitriy Sintsov <questpc@rambler.ru>',
    'version' => '0.3',
    'url' => 'http://www.mediawiki.org/wiki/Extension:PrettyPhoto',
    'descriptionmsg' => 'Show images on popup window',
);


$wgResourceModules['jquery.prettyphoto'] = array(
	'scripts' => 'modules/jquery.prettyPhoto.js',
	# todo: figure out why the border style is broken when
	# $wgResourceLoaderDebug = false;
	# 'styles' => 'modules/prettyPhoto.css',
	'dependencies' => 'jquery',
	'localBasePath' => dirname( __FILE__ ),
	'remoteExtPath' => 'PrettyPhoto',
);
$wgResourceModules['ext.prettyphoto'] = array(
	'scripts' => 'modules/init.js',
	'dependencies' => array( 'jquery.prettyphoto' ),
	'localBasePath' => dirname( __FILE__ ),
	'remoteExtPath' => 'PrettyPhoto',
);

if ( $wgParserConf['class'] !== 'Parser' ) {
	die( 'Extension:PrettyPhoto cannot override non-default Parser' );
}
$wgParserConf['class'] = 'PrettyPhotoParser';

class PrettyPhotoParser extends Parser {

	function makeImage( $title, $options, $holders = false ) {
		return PrettyPhoto::apply( parent::makeImage( $title, $options, $holders ) );
	}

	function renderImageGallery( $text, $params ) {
		return PrettyPhoto::apply( parent::renderImageGallery( $text, $params ) );
	}

} /* end of PrettyPhotoParser class */

$wgHooks['BeforePageDisplay'][] =
new PrettyPhoto();

class PrettyPhoto {

	static function onBeforePageDisplay( $out, $skin ) {
		global $wgScriptPath;
		$out->addStyle( "{$wgScriptPath}/extensions/PrettyPhoto/modules/prettyPhoto.css" );
		$out->addModules( 'ext.prettyphoto' );
		return true;
	}

	protected static function addHtmlBody( $html ) {
		return '<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8" /></head><body>' . $html . '</body></html>';
	}

	protected static function removeHtmlBody( $html ) {
		# non-mb versions are safe because we are searching for ASCII codes < 128
		if ( ($pos1 = strpos( $html, '<body>' )) !== false &&
				($pos2 = strrpos( $html, '</body>' )) !== false ) {
			$pos1 += 6; // length of '<body>'
			if ( $pos1 < $pos2 ) {
				return trim( substr( $html, $pos1, $pos2 - $pos1 ) );
			}
		}
		return false;
	}

	/**
	 * When there is a huge image, will use ImagePage constraints (usually max 800x600).
	 * Parts from ImagePage MW 1.19.2
	 */
	protected static function getImagePreviewUrl( Title $fileTitle ) {
		global $wgRequest, $wgUser;
		global $wgImageLimits;
		$image = RepoGroup::singleton()->getLocalRepo()->newFile( $fileTitle );
		$sizeSel = intval( $wgUser->getOption( 'imagesize' ) );
		if ( !isset( $wgImageLimits[$sizeSel] ) ) {
			$sizeSel = User::getDefaultOption( 'imagesize' );

			// The user offset might still be incorrect, specially if
			// $wgImageLimits got changed (see bug #8858).
			if ( !isset( $wgImageLimits[$sizeSel] ) ) {
				// Default to the first offset in $wgImageLimits
				$sizeSel = 0;
			}
		}
		list( $maxWidth, $maxHeight ) = $wgImageLimits[$sizeSel];
		$page = $wgRequest->getIntOrNull( 'page' );
		if ( is_null( $page ) ) {
			$params = array();
			$page = 1;
		} else {
			$params = array( 'page' => $page );
		}
		$width_orig = $image->getWidth( $page );
		$width = $width_orig;
		$height_orig = $image->getHeight( $page );
		$height = $height_orig;
		if ( $width <= $maxWidth && $height <= $maxHeight ) {
			return $image->getUrl();
		}
		# Calculate the thumbnail size.
		# First case, the limiting factor is the width, not the height.
		if ( $width / $height >= $maxWidth / $maxHeight ) {
			$height = round( $height * $maxWidth / $width );
			$width = $maxWidth;
			# Note that $height <= $maxHeight now.
		} else {
			$newwidth = floor( $width * $maxHeight / $height );
			$height = round( $height * $newwidth / $width );
			$width = $newwidth;
			# Note that $height <= $maxHeight now, but might not be identical
			# because of rounding.
		}
		$params['width'] = $width;
		$params['height'] = $height;
		$thumbnail = $image->transform( $params );
		return $thumbnail->getUrl();
	}

	static function apply( $makeImageHtml ) {
		$tree = new DOMDocument();
		libxml_use_internal_errors( true );
		$loadResult = @$tree->loadHTML( static::addHtmlBody( $makeImageHtml ) );
		libxml_clear_errors();
		if ( !$loadResult ) {
			return $makeImageHtml;
		}
		$xpath = new DOMXPath( $tree );
		$anchors = $xpath->query( "//a[@class!='internal']" );
		if ( $anchors->length === 0 ) {
			return $makeImageHtml;
		}
		$thumbCapDesc = array();
		# [[image:|description]] link ?
		$thumbcaptions = $xpath->query( "//div[@class='thumbcaption']/child::text()[last()]" );
		if ( $thumbcaptions->length === 0 ) {
			# image <gallery> ?
			$thumbcaptions = $xpath->query( "//div[@class='gallerytext']/child::text()" );
		}
		foreach ( $thumbcaptions as $thumbcaption ) {
			$thumbCapDesc[] = trim( $thumbcaption->wholeText );
		}
		$anchorIdx = 0;
		foreach ( $anchors as $anchor ) {
			$currThumbDesc = array_key_exists( $anchorIdx, $thumbCapDesc ) ? $thumbCapDesc[$anchorIdx] : '';
			$anchorClass = $anchor->hasAttribute( 'class' ) ? $anchor->getAttribute( 'class' ) . ' ' : '';
			$fileName = explode( ':', $anchor->getAttribute( 'href' ) );
			$fileName = array_pop( $fileName );
			$fileTitle = Title::newFromText( $fileName, NS_FILE );
			if ( $fileTitle instanceof Title ) {
				$anchor->setAttribute( 'class', "{$anchorClass}ext-prettyphoto" );
				$anchor->setAttribute( 'href', static::getImagePreviewUrl( $fileTitle ) );
				$description = $anchor->hasAttribute( 'title' ) ?
					$anchor->getAttribute( 'title' ) : $currThumbDesc;
				if ( $description === '' ) {
					$description = $fileName;
				}
				$anchor->setAttribute( 'title', $description );
				$anchor->setAttribute( 'data-prettyphoto-link', '<a href="' . rawUrlEncode( $fileTitle->getLinkUrl() ) . '" target="_blank">' . $description . '</a>' );
			}
			$anchorIdx++;
		}
		$result = static::removeHtmlBody( $tree->saveHTML() );
		return is_string( $result ) ? $result : $makeImageHtml;
	}

} /* end of PrettyPhoto class */
