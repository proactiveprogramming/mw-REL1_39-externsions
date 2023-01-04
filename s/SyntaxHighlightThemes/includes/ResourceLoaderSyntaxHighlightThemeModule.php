<?php

use MediaWiki\MediaWikiServices;
use MediaWiki\Shell\Shell;
  
class ResourceLoaderSyntaxHighlightThemeModule extends ResourceLoaderFileModule {

	protected $targets = [ 'desktop', 'mobile' ];

	public function getStyleFiles( ResourceLoaderContext $context ) {
		static $themes = null;
		if ( !$themes ) {
			$themes = require __DIR__ . '/../SyntaxHighlight.themes.php';
		}

		$userManager = MediaWikiServices::getInstance()->getUserOptionsManager();
        $user = RequestContext::getMain()->getUser();
		$theme = RequestContext::getMain()->getRequest()->getRawVal( 'usehighlighttheme' );
		if ( !isset($themes[$theme]) ) {
        	$theme = $userManager->getOption($user, 'syntaxhighlight-theme') ?? 'default';
		}
		$this->styles = [ 'pygments.' . $theme . '.css' ];
		return parent::getStyleFiles( $context );
	}

    public function shouldEmbedModule( ResourceLoaderContext $context ) {
		return true;
	}

}
