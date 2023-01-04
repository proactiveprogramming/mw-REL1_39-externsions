<?php
if( !defined( 'MEDIAWIKI' ) ) exit;
/**
 * Class file for the FacebookComments extension
 *
 * @ingroup Extensions
 * @author Jmkim dot com
 * @license GNU Public License
 */
class FacebookComments {
	static function getURL() {
		if ( isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']=='on' ) { $protocol =  'https'; $port = '443'; }
		else { $protocol = 'http'; $port = '80'; }
		$port = $_SERVER['SERVER_PORT']==$port ? '' : ':' . $_SERVER['SERVER_PORT'];
		return $protocol.'://'.$_SERVER['HTTP_HOST'].$port;
	}
	static function renderFacebookComments( $skin, &$text ) {
		global $mediaWiki, $wgOut, $wgArticlePath, $wgScriptPath;
		global $wgFacebookCommonScriptWritten, $wgFacebookAppId;
		global $wgFacebookCommentsNumPosts, $wgFacebookCommentsWidth, $wgFacebookCommentsColorscheme;
		$title = $skin->getTitle();
		if( isset($_REQUEST['redirect']) ) return true;
		if( $mediaWiki->getAction() != 'view' ) return true;
		if( !$title->exists() ) return true;
		if( $title->getNamespace() != 0) return true;
		$fbcomments = '';
		if( !isset($wgFacebookAppId) ) $wgFacebookAppId = '';
		if( !isset($wgFacebookCommonScriptWritten) || !$wgFacebookCommonScriptWritten ) {
			$wgFacebookCommonScriptWritten = true;
			$fbcomments .= '<div id="fb-root"></div><script src="http://connect.facebook.net/en_US/all.js#xfbml=1&appId='.$wgFacebookAppId.'"></script>';
		}
		if( isset($wgArticlePath) ) $href = str_replace('$1','',FacebookComments::getURL().$wgArticlePath).$title;
		else $href = FacebookComments::getURL().$wgScriptPath.'/index.php/'.$title;
		$fbcomments .= '<br><div class="fb-comments" data-href="'.$href.'"';
		$fbcomments .= ' data-num-posts="'.$wgFacebookCommentsNumPosts.'"';
		$fbcomments .= ' data-width="'.$wgFacebookCommentsWidth.'"';
		$fbcomments .= ' data-colorscheme="'.$wgFacebookCommentsColorscheme.'"';
		$fbcomments .= '></div>';
		$wgOut->addHTML($fbcomments);
		return true;
	}
}