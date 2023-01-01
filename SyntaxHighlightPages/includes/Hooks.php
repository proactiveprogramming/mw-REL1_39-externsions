<?php
namespace MediaWiki\Extension\SyntaxHighlightPages;

class Hooks {
	public static function onContentHandlerDefaultModelFor( \Title $title, &$model ) {
		$parts = explode('.', $title->getDBkey());
		$ext = end($parts);
		$map = Content::getExtensionMap();
		if ($title->isContentPage() && isset($map[$ext])) {
			$model = Content::MODEL;
			return false;
		}
		return true;
	}

	public static function onBaseTemplateToolbox( \BaseTemplate $baseTemplate, array &$toolbox ) {
		$title = $baseTemplate->getSkin()->getTitle();
		if ($title->getContentModel() == Content::MODEL){
			$toolbox['download'] = ['text'=>'Download', 'href'=>$title->getLocalURL('action=raw'), 'download'=>$title->getSubpageText()];
		}
	}
}
