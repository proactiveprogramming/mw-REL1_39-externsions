<?php

/**
 * Hooks used by NewPageTemplateSelector extension 
 */
class NewPageTemplateSelectorHooks {

	/**
	 * The hook will be runned after parsing is done but before html is added to a page output.
	 * Details are here: https://www.mediawiki.org/wiki/Manual:Hooks/OutputPageParserOutput
	 */
	public static function onBeforeHtmlAddedToOutput( OutputPage &$out, ParserOutput $parserOutput ) {
		/**
		 * This is the perfect place to load our frontend.
		 * The frontend module 'ext.NewPageTemplateSelector' is defined in extension.json
		 */ 
		$out->addModules( 'ext.NewPageTemplateSelector' );
		return true;
	}

	
	/**
	 * We extend parser here.
	 * Parser will process our custom tag: <NewPageTemplateSelectorHooks>
	 */
	public static function onParserSetup( Parser $parser ) {
		$parser->setHook( 'NewPageTemplateSelector', 'NewPageTemplateSelectorHooks::processNewPageTemplateSelectorTag' );
		return true;
	}


	/**
	 * Implementation of the '<NewPageTemplateSelector>' tag processing
	 */
	public static function processNewPageTemplateSelectorTag( $input, array $args, Parser $parser, PPFrame $frame ) {
		global $wgScript;

		$inLinkStr = 'Create page';
		$pageNamePlaceholder = 'Please enter page name';

		if( isset( $args['placeholder'] ) ) {
			$pageNamePlaceholder = $args['placeholder'];
		}
		else {
			$pageNamePlaceholder = 'Please enter page name';
		}

		if( isset( $args['templates'] ) ) {
			$templates = explode(',', $args['templates']);
		}
		else {
			$templates = [];
		}

		$templateOptions = [];
		foreach ($templates as $template) {
			$trimmedTemplate = trim( $template );
			$templateOptions[] = [
				'data' => $trimmedTemplate,
				'value' => $trimmedTemplate
			];
		}
		
		$out = $parser->getOutput();
		OutputPage::setupOOUI();
		$out->setEnableOOUI( true );

		$pagenameInput = new OOUI\FieldLayout(
			new OOUI\TextInputWidget( [
				'name' => 'title',
				'placeholder' => $pageNamePlaceholder,
				'autofocus' => true,
				'spellcheck' => true,
				'classes' => [ 
					'cpnf_pagename' 
				],
				'required' => true,
			] ),
			[
				'align' => 'left',
			]
		);

		$templateSelector = new OOUI\FieldLayout(
			new OOUI\DropdownInputWidget( [
				'name' => 'preload',
				'options' => $templateOptions,
			] ),
			[
				'align' => 'left',
			]
		);

		$buttonInput = new OOUI\FieldLayout(
			new OOUI\ButtonInputWidget( [
				'label' => $inLinkStr,
				'type' => 'submit',
				'flags' => [ 'primary', 'progressive' ],
			] ),
			[
				'align' => 'left',
			]
		);

		$formItems = [
			$pagenameInput,
		];
		if ( count( $templateOptions ) > 0 ) {
			$formItems[] = $templateSelector;
		}
		$formItems[] = $buttonInput;
		$formItems[] = NewPageTemplateSelectorHooks::createFakeOouiElement('create', 'Create page');
		$formItems[] = NewPageTemplateSelectorHooks::createFakeOouiElement('veaction', 'edit');

		$form = new OOUI\FormLayout( [
			'method' => 'GET',
			'action' => $wgScript,
			'items' => [
				new OOUI\HorizontalLayout( [
					'items' => $formItems,
				] )
			]
		] );

		return $form;
	}

	public static function createFakeOouiElement($name, $value){
		$elem = new OOUI\FieldLayout(
			new OOUI\HiddenInputWidget([
				'name' => $name,
				'value' => $value,
			]),
			[
				'align' => 'left',
			]
		);

		return $elem;
	}
}
