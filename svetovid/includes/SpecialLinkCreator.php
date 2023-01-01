<?php

use MediaWiki\MediaWikiServices;

class SpecialLinkCreator extends SpecialPage {

	private const CASES = [
		'nom',
		'gen',
		'dat',
		'acc',
		'inst',
		'loc',
		'voc'
	];

	function __construct() {
		parent::__construct( 'LinkCreator', 'svetovid-search' );
	}

	function execute( $subpage ) {
		$this->checkPermissions();
		$out = $this->getOutput();
		$out->enableOOUI();
		$this->addHelpLink( 'Extension:Svetovid' );

		if ( $subpage ) {
			$subTitle = Title::newFromText( $subpage );
			if ( $subTitle ) {
				$out->addBacklinkSubtitle( $subTitle );
				$subpage = $subTitle->getPrefixedText();
			} else {
				$subpage = '';
			}
		}
		$html = $this->getFirstStageGUI( $subpage );

		$out->addHTML( $html );
		$out->setPageTitle( $this->msg( 'linkcreator-title' ) );
		$out->addModules( 'ext.svetovid.creator' );
		$out->addModuleStyles( 'ext.svetovid.creator.styles' );
	}

	private function getFirstStageGUI( ?string $subpage ) {
		$config = MediaWikiServices::getInstance()->getMainConfig();
		$contentNamespaces = $config->get('ContentNamespaces');

		$html = '';

		$html .= new OOUI\ActionFieldLayout(
			new MediaWiki\Widget\TitleInputWidget( [
				'placeholder' => $this->msg( "svetovid-page-title-placeholder" )->escaped(),
				'id' => 'sv-page-input',
				'infusable' => true,
				'value' => $subpage ?? ''
			] ),
			new OOUI\ButtonWidget( [
				'label' => 'OK',
				'flags' => [
					'primary',
					'progressive'
				],
				'id' => 'sv-page-input-ok',
				'infusable' => true
			] ),
			[
				'label' => $this->msg( "svetovid-page-title-label" )->escaped(),
				'align' => 'top'
			]
		);
		$html .= new OOUI\LabelWidget( [
			'label' => '',
			'id' => 'sv-page-link-stats',
			'infusable' => true
		] );

		$html .= '<br /><br />';

		$grammar = new OOUI\FieldsetLayout( [
			'label' => $this->msg( "svetovid-grammar-section-title" )->escaped(),
			'id' => 'sv-grammar-set',
			'infusable' => true
		] );

		$grammar->addItems( [
			new OOUI\ActionFieldLayout(
				new OOUI\TextInputWidget( [
					'placeholder' => $this->msg( "svetovid-search-for-placeholder" )->escaped(),
					'id' => 'sv-search-for-input',
					'infusable' => true
				] ),
				new OOUI\ButtonWidget( [
					'label' => $this->msg( "svetovid-search-for-change" )->escaped(),
					'id' => 'sv-search-for-change',
					'infusable' => true,
					'flags' => [
						'primary',
						'progressive'
					]
				] ),
				[
					'label' => $this->msg( "svetovid-search-for-label" )->escaped(),
					'align' => 'top'
				]
			)
		] );

		$html .= $grammar;

		$cbSg = new OOUI\FieldLayout(
			new OOUI\CheckboxInputWidget( [
				'selected' => true,
				'id' => 'sv-gr-sg',
				'infusable' => true
			] ),
			[
				'label' => $this->msg( "svetovid-grammar-sg" )->escaped(),
				'align' => 'inline',
				'classes' => [ 'sv-table-center' ]
			]
		);
		$cbPl = new OOUI\FieldLayout(
			new OOUI\CheckboxInputWidget( [
				'selected' => true,
				'id' => 'sv-gr-pl',
				'infusable' => true
			] ),
			[
				'label' => $this->msg( "svetovid-grammar-pl" )->escaped(),
				'align' => 'inline',
				'classes' => [ 'sv-table-center' ]
			]
		);

		$table = '<br /><table id="sv-grammar-table" style="max-width: 50em; visibility: collapse; width: 100%"><tr><th></th><th>' .
			$cbSg . '</th><th>' .
			$cbPl . '</th></tr>';

		foreach ( self::CASES as $case ) {
			$table .= '<tr><td>' . $this->msg( 'svetovid-grammar-' . $case )->escaped() . '</td>';
			$table .= '<td>' . new OOUI\TextInputWidget( [
					'id' => 'sv-gr-inp-sg-' . $case,
					'infusable' => true
				] ) . '</td>';
			$table .= '<td>' . new OOUI\TextInputWidget( [
					'id' => 'sv-gr-inp-pl-' . $case,
					'infusable' => true
				] ) . '</td>';
			$table .= '</tr>';
		}

		$html .= $table . '</table><br />';

		$options = new OOUI\FieldsetLayout( [
			'label' => $this->msg( "svetovid-search-options-section-title" )->escaped(),
			'id' => 'sv-options-set',
			'infusable' => true
		] );

		$options->addItems( [
			new OOUI\FieldLayout(
				new MediaWiki\Widget\NamespacesMultiselectWidget( [
					'id' => 'sv-ns-select',
					'infusable' => true,
					'default' => array_map( 'strval', $contentNamespaces )
				] ),
				[
					'label' => $this->msg( "svetovid-search-in-ns-label" )->escaped(),
					'align' => 'top'
				]
			),
			new OOUI\FieldLayout(
				new OOUI\CheckboxInputWidget( [
					'id' => 'sv-omit-linkshere',
					'infusable' => true,
					'selected' => true
				] ),
				[
					'label' => $this->msg( "svetovid-omit-linkshere-label" )->escaped(),
					'align' => 'inline'
				]
			)
		] );

		$html .= $options . '<br />';

		$html .= new OOUI\ButtonWidget( [
			'label' => $this->msg( "svetovid-search" )->escaped(),
			'flags' => [
				'primary',
				'progressive'
			],
			//'icon' => 'search',
			'id' => 'sv-search-button',
			'infusable' => true
		] );

		$html .= '<br /><div class="sv-search-footer">' . $this->msg( "svetovid-search-footer" )->parse() . '</div>';

		global $wgSvetovidDefaultNamespaces;
		$this->getOutput()->addJsConfigVars( [
			'wgContentNamespaces' => $contentNamespaces ?? [],
			'wgSvetovidDefaultNamespaces' => $wgSvetovidDefaultNamespaces ?? []
		] );

		return $html;
	}

	protected function getGroupName() {
		return 'advancedBacklinks';
	}
}
