<?php
/**
 * HelloWorld SpecialPage for nodos extension
 *
 * @file
 * @ingroup Extensions
 */
require_once ('ImportNodos.php');

class SpecialNodos extends SpecialPage {
	public function __construct() {
		parent::__construct( 'Nodos' );
	}

	/**
	 * Show the page to the user
	 *
	 * @param string $sub The subpage string argument (if any).
	 *  [[Special:HelloWorld/subpage]].
	 */
	public function execute( $sub ) {
		$out = $this->getOutput();

		$out->setPageTitle( $this->msg( 'nodos-title' ) );

		$out->addWikiMsg( 'nodos-intro' );

		$formDescriptor = [];

		$htmlForm = HTMLForm::factory( 'ooui', $formDescriptor, $this->getContext(), 'nodosform' );

		$htmlForm->setSubmitText( 'Generar modelo' );
		$htmlForm->setSubmitCallback( [ 'SpecialNodos', 'trySubmit' ] );

		$htmlForm->show();
	}

	static function trySubmit() {
        $nodosOntology = $_SERVER['DOCUMENT_ROOT'] . '/extensions/nodos/data/NodosOntology.xml';
        $nodosImporter = new ImportNodos();
        return $nodosImporter->doImport($nodosOntology);;
	}
}

