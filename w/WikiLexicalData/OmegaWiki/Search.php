<?php

require_once "Wikidata.php";
require_once "Transaction.php";
require_once "RecordSet.php";
require_once "Editor.php";
require_once "WikiDataAPI.php";
require_once "OmegaWikiAttributes.php";
require_once "OmegaWikiRecordSets.php";
require_once "OmegaWikiEditors.php";
require_once "WikiDataGlobals.php";

/**
 * @todo Check if this class is used or not, I can not find how to use this.
 *	Was this default app replaced by special page Data search? ~he
 */
class Search extends DefaultWikidataApplication {
	function view() {
		global
			$wgOut, $wgTitle;

		parent::view();

		$spelling = $wgTitle->getText();
		$wgOut->addHTML( '<h1>Words matching <i>' . $spelling . '</i> and associated meanings</h1>' );
		$wgOut->addHTML( '<p>Showing only a maximum of 100 matches.</p>' );
		$wgOut->addHTML( $this->searchText( $spelling ) );
	}

	function searchText( $text ) {
		$dc = wdGetDataSetContext();
		$dbr = wfGetDB( DB_REPLICA );

		$queryResult = $dbr->selectSQLText(
			[
				'exp' => '{$dc}_expression',
				'synt' => '{$dc}_syntrans'
			],
			[
				'INSTR( LOWER( exp.spelling ), LOWER( {$text} ) )  AS position', // LCASE replaced with LOWER for SQLite compatibility
				'synt.defined_meaning_id AS defined_meaning_id',
				'exp.spelling AS spelling',
				'exp.language_id AS language_id'
			],
			[
				'exp.expression_id = synt.expression_id',
				'synt.identical_meaning = 1',
				'exp.remove_transaction_id' => null,
				'synt.remove_transaction_id' => null,
				'spelling LIKE ' . $dbr->addQuotes( "%$text%" )
			], __METHOD__,
			[
				'ORDER BY' => 'position ASC',
				'ORDER BY' => 'exp.spelling ASC',
				'LIMIT' => 100
			]
		);
		var_dump( $queryResult );
		die;
		// phpcs:ignore Squiz.PHP.NonExecutableCode.Unreachable
		list( $recordSet, $editor ) = getSearchResultAsRecordSet( $queryResult );
		// return $sql;
		// phpcs:ignore Squiz.PHP.NonExecutableCode.Unreachable
		return $editor->view( new IdStack( "expression" ), $recordSet );
	}
}

function getSearchResultAsRecordSet( $queryResult ) {
	$o = OmegaWikiAttributes::getInstance();
	global $definedMeaningReferenceType;

	$dbr = wfGetDB( DB_REPLICA );
	$spellingAttribute = new Attribute( "found-word", "Found word", "short-text" );
	$languageAttribute = new Attribute( "language", "Language", "language" );

	$expressionStructure = new Structure( $spellingAttribute, $languageAttribute );
	$expressionAttribute = new Attribute( "expression", "Expression", $expressionStructure );

	$definedMeaningAttribute = new Attribute( WLD_DEFINED_MEANING, "Defined meaning", $definedMeaningReferenceType );
	$definitionAttribute = new Attribute( "definition", "Definition", "definition" );

	$meaningStructure = new Structure( $definedMeaningAttribute, $definitionAttribute );
	$meaningAttribute = new Attribute( "meaning", "Meaning", $meaningStructure );

	$recordSet = new ArrayRecordSet( new Structure( $o->definedMeaningId, $expressionAttribute, $meaningAttribute ), new Structure( $o->definedMeaningId ) );

	foreach ( $queryResult as $row ) {
		$expressionRecord = new ArrayRecord( $expressionStructure );
		$expressionRecord->setAttributeValue( $spellingAttribute, $row->spelling );
		$expressionRecord->setAttributeValue( $languageAttribute, $row->language_id );

		$meaningRecord = new ArrayRecord( $meaningStructure );
		$meaningRecord->setAttributeValue( $definedMeaningAttribute, getDefinedMeaningReferenceRecord( $row->defined_meaning_id ) );
		$meaningRecord->setAttributeValue( $definitionAttribute, getDefinedMeaningDefinition( $row->defined_meaning_id ) );

		$recordSet->addRecord( [ $row->defined_meaning_id, $expressionRecord, $meaningRecord ] );
	}

	$expressionEditor = new RecordTableCellEditor( $expressionAttribute );
	$expressionEditor->addEditor( new SpellingEditor( $spellingAttribute, new SimplePermissionController( false ), false ) );
	$expressionEditor->addEditor( new LanguageEditor( $languageAttribute, new SimplePermissionController( false ), false ) );

	$meaningEditor = new RecordTableCellEditor( $meaningAttribute );
	$meaningEditor->addEditor( new DefinedMeaningReferenceEditor( $definedMeaningAttribute, new SimplePermissionController( false ), false ) );
	$meaningEditor->addEditor( new TextEditor( $definitionAttribute, new SimplePermissionController( false ), false, true, 75 ) );

	$editor = createTableViewer( null );
	$editor->addEditor( $expressionEditor );
	$editor->addEditor( $meaningEditor );

	return [ $recordSet, $editor ];
}