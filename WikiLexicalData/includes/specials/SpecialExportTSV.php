<?php
if ( !defined( 'MEDIAWIKI' ) ) {
	die();
}

class SpecialExportTSV extends SpecialPage {

	function __construct() {
		parent::__construct( 'ExportTSV' );
	}

	function execute( $par ) {
		global $wgWldOwScriptPath, $wgDBprefix;
		require_once $wgWldOwScriptPath . "WikiDataAPI.php"; // for bootstrapCollection

		$output = $this->getOutput();
		$request = $this->getRequest();

		$dbr = wfGetDB( DB_REPLICA );
		$dc = wdGetDataSetcontext();
		$filterType = null;
		$topicAttributeId = 1150889; // ugly but will do for now.

		$collectionId = $request->getText( 'collection' );
		$classId = $request->getText( 'class' );
		$topicId = $request->getText( 'topic' );

		// get the collection to export.
		if ( $request->getText( 'createcol' ) && $collectionId ) {
			$filterType = 'collection';
		} elseif ( $request->getText( 'createcla' ) && $classId ) {
			$filterType = 'class';
		} elseif ( $request->getText( 'createtopic' ) && $topicId ) {
			$filterType = 'topic';
		}

		if ( $filterType && $request->getText( 'languages' ) ) {
			// render the tsv file

			// get the languages requested, turn into an array, trim for spaces.
			$isoCodes = explode( ',', $request->getText( 'languages' ) );
			for ( $i = 0; $i < count( $isoCodes ); $i++ ) {
				$isoCodes[$i] = trim( $isoCodes[$i] );
				if ( !getLanguageIdForIso639_3( $isoCodes[$i] ) ) {
					$output->setPageTitle( wfMessage( 'ow_exporttsv_export_failed' )->text() );
					$output->addHTML( wfMessage( 'ow_impexptsv_unknown_lang', $isoCodes[$i] )->text() );
					return false;
				}
			}

			$output->disable();

			$languages = $this->getLanguages( $isoCodes );
			$isoLookup = $this->createIsoLookup( $languages );
			$downloadFileName = $this->createFileName( $isoCodes );

			// Force the browser into a download
			header( 'Content-Type: text/tab-separated-values;charset=utf-8' );
			header( 'Content-Disposition: attachment; filename="' . $downloadFileName . '"' ); // attachment

			// separator character used.
			$sc = "\t";

			echo( pack( 'CCC', 0xef, 0xbb, 0xbf ) );
			// start the first row: column names
			echo( 'defined meaning id' . $sc . 'defining expression' );
			foreach ( $isoCodes as $isoCode ) {
				echo( $sc . 'definition_' . $isoCode . $sc . 'translations_' . $isoCode );
			}
			echo( "\r\n" );

			$sqltables = [
				'dm' => "{$dc}_defined_meaning",
				'exp' => "{$dc}_expression"
			];
			$sqlcond = [
				'dm.expression_id = exp.expression_id',
				'dm.remove_transaction_id' => null,
				'exp.remove_transaction_id' => null
			];

			if ( $filterType == 'collection' ) {
				$sqltables['col'] = "{$dc}_collection_contents";
				$sqlcond['col.collection_id'] = $collectionId;
				$sqlcond[] = 'col.member_mid=dm.defined_meaning_id';
				$sqlcond['col.remove_transaction_id'] = null;
			} elseif ( $filterType == 'class' ) {
				$sqltables['cla'] = "{$dc}_class_membership";
				$sqlcond['cla.class_mid'] = $classId;
				$sqlcond[] = 'cla.class_member_mid=dm.defined_meaning_id';
				$sqlcond['cla.remove_transaction_id'] = null;
			} elseif ( $filterType == 'topic' ) {
				$sqltables['oav'] = "{$dc}_option_attribute_values";
				$sqlcond['oav.option_id '] = $topicId;
				$sqlcond[] = 'oav.object_id=dm.defined_meaning_id';
				$sqlcond['oav.remove_transaction_id'] = null;
			}

			// get all the defined meanings in the collection
			$queryResult = $dbr->select(
				$sqltables,
				[ 'dm.defined_meaning_id' , 'exp.spelling' ],
				$sqlcond,
				__METHOD__,
				[ 'ORDER BY' => 'exp.spelling' ]
			);

			foreach ( $queryResult as $row ) {
				$dm_id = $row->defined_meaning_id;
				// echo the defined meaning id and the defining expression
				echo( $dm_id );
				echo( "\t" . $row->spelling );

				// First we'll fill an associative array with the definitions and
				// translations. Then we'll use the isoCodes array to put them in the
				// proper order.

				// the associative array holding the definitions and translations
				$data = [];

				// ****************************
				// query to get the definitions
				// ****************************
				$qry = 'SELECT txt.text_text, trans.language_id ';
				$qry .= "FROM {$wgDBprefix}{$dc}_text txt, {$wgDBprefix}{$dc}_translated_content trans, {$wgDBprefix}{$dc}_defined_meaning dm ";
				$qry .= 'WHERE txt.text_id = trans.text_id ';
				$qry .= 'AND trans.translated_content_id = dm.meaning_text_tcid ';
				$qry .= "AND dm.defined_meaning_id = $dm_id ";
				$qry .= 'AND trans.language_id IN (';
				for ( $i = 0; $i < count( $languages ); $i++ ) {
					$language = $languages[$i];
					if ( $i > 0 ) {
						$qry .= ",";
					}
					$qry .= $language['language_id'];
				}
				$qry .= ') AND ' . getLatestTransactionRestriction( 'trans' );
				$qry .= 'AND ' . getLatestTransactionRestriction( 'dm' );

				// wfDebug($qry."\n"); // uncomment this if you accept having 1700+ queries in the log

				// I want to use this routine instead of the above, but an
				// error I can not solve prohibits me.
				/*

				$includeLanguages = null;
				for ( $i = 0; $i < count( $languages ); $i++ ) {
					$language = $languages[$i];
					if ( $i > 0 )
						$includeLanguages .= ",";
					$includeLanguages .= $language['language_id'];
				}
				$sqltables = array(
					'txt' => "{$dc}_text",
					'trans' => "{$dc}_translated_content",
					'dm' => "{$dc}_defined_meaning"
				);
				$sqlfields = array(
					'txt.text_text',
					'trans.language_id'
				);
				$sqlcond = array(
					'txt.text_id = trans.text_id',
					'txt.translated_content_id = dm.meaning_text_tcid',
					'dm.defined_meaning_id' => $dm_id,
					"trans.language_id IN ({$includeLanguages})",
					'trans.remove_transaction_id' => null,
					'dm.remove_transaction_id' => null
				);
				$qry2 = array(
					$sqltables,
					$sqlfields,
					$sqlcond, __METHOD__
				);

				$queryResult = $dbr->select(
					$sqltables,
					$sqlfields,
					$sqlcond, __METHOD__
				);
				*/

				$definitions = $dbr->query( $qry );
				foreach ( $definitions as $rowdef ) {
					// $key becomes something like def_eng
					$key = 'def_' . $isoLookup['id' . $rowdef->language_id];
					$data[$key] = $rowdef->text_text;
				}

				// *****************************
				// query to get the translations
				// *****************************
				$qry = "SELECT exp.spelling, exp.language_id ";
				$qry .= "FROM {$wgDBprefix}{$dc}_expression exp ";
				$qry .= "INNER JOIN {$wgDBprefix}{$dc}_syntrans trans ON exp.expression_id=trans.expression_id ";
				$qry .= "WHERE trans.defined_meaning_id=$dm_id ";
				$qry .= "AND " . getLatestTransactionRestriction( "exp" );
				$qry .= "AND " . getLatestTransactionRestriction( "trans" );

				// wfDebug($qry."\n"); // uncomment this if you accept having 1700+ queries in the log

				$translations = $dbr->query( $qry );
				foreach ( $translations as $rowtrans ) {
					// qry gets all languages, we filter them here. Saves an order
					// of magnitude execution time.
					if ( isset( $isoLookup['id' . $rowtrans->language_id] ) ) {
						// $key becomes something like trans_eng
						$key = 'trans_' . $isoLookup['id' . $rowtrans->language_id];
						if ( !isset( $data[$key] ) ) {
							$data[$key] = $rowtrans->spelling;
						} else {
							$data[$key] = $data[$key] . '|' . $rowtrans->spelling;
						}
					}
				}

				// now that we have everything, output the row.
				foreach ( $isoCodes as $isoCode ) {
					// if statements save a bunch of notices in the log about
					// undefined indices.
					echo( "\t" );
					if ( isset( $data['def_' . $isoCode] ) ) {
						echo( $this->escapeDelimitedValue( $data['def_' . $isoCode] ) );
					}
					echo( "\t" );
					if ( isset( $data['trans_' . $isoCode] ) ) {
						echo( $data['trans_' . $isoCode] );
					}
				}
				echo( "\r\n" );
			}

		} else {
			$collections = [];
			$topics = [];

			// Get the collections
			$colResults = $dbr->select(
				[ 'col' => "{$dc}_collection", 'dm' => "{$dc}_defined_meaning", 'exp' => "{$dc}_expression" ],
				[ 'col.collection_id', 'exp.spelling' ],
				[ 'col.remove_transaction_id' => null ],
				__METHOD__,
				[],
				[
					'dm' => [ 'INNER JOIN', [ 'col.collection_mid=dm.defined_meaning_id' ] ],
					'exp' => [ 'INNER JOIN', [ 'dm.expression_id=exp.expression_id' ] ]
				]
			);
			foreach ( $colResults as $rowcol ) {
				$collections[$rowcol->collection_id] = $rowcol->spelling;
			}

			$topicResults = $dbr->select(
				[ 'oao' => "{$dc}_option_attribute_options", 'dm' => "{$dc}_defined_meaning", 'exp' => "{$dc}_expression" ],
				[ 'oao.option_id', 'exp.spelling' ],
				[
					'oao.attribute_id' => $topicAttributeId,
					'oao.remove_transaction_id' => null
				], __METHOD__,
				[],
				[
					'dm' => [ 'INNER JOIN', [ 'oao.option_mid=dm.defined_meaning_id' ] ],
					'exp' => [ 'INNER JOIN', [ 'dm.expression_id=exp.expression_id' ] ]
				]
			);
			foreach ( $topicResults as $row ) {
				$topics[$row->option_id] = $row->spelling;
			}

			// render the page
			$output->setPageTitle( wfMessage( 'ow_exporttsv_title' )->text() );
			$output->addHTML( wfMessage( 'ow_exporttsv_header' )->text() );

			// all DM from a collection
			$output->addHTML( getOptionPanel(
				[
					wfMessage( 'ow_Collection' )->text() => getSelect( 'collection', $collections, '376322' ),
					wfMessage( 'prefs-ow-lang' )->text() => getTextBox( 'languages', 'ita, eng, deu, fra, cat' ),
				],
				'', [ 'createcol' => wfMessage( 'ow_create' )->text() ]
			) );

			// all DM from a class
			$output->addHTML( getOptionPanel(
				[
					wfMessage( 'ow_Class' )->text() => getSuggest( 'class', 'class' ),
					wfMessage( 'prefs-ow-lang' )->text() => getTextBox( 'languages', 'ita, eng, deu, fra, cat' ),
				],
				'', [ 'createcla' => wfMessage( 'ow_create' )->text() ]
			) );

			// all DM from a given subject/topic
			$output->addHTML( getOptionPanel(
				[
					'topic' => getSelect( 'topic', $topics ),
					wfMessage( 'prefs-ow-lang' )->text() => getTextBox( 'languages', 'ita, eng, deu, fra, cat' ),
				],
				'', [ 'createtopic' => wfMessage( 'ow_create' )->text() ]
			) );
		}
	}

	/* HELPER METHODS START HERE */

	function escapeDelimitedValue( $value ) {
		$newValue = str_replace( '"', '""', $value );
		// Unfortunately, excell doesn't handle line brakes correctly, even if they are in quotes.
		// we'll just remove them.
		$newValue = str_replace( "\r\n", ' ', $value );
		$newValue = str_replace( "\n", ' ', $value );
		// quoting the string is always allowed, so lets check for all possible separator characters
		if ( $value != $newValue || strpos( $value, ',' ) || strpos( $value, ';' ) || strpos( $value, '\t' ) ) {
			$newValue = '"' . $newValue . '"';
		}
		return $newValue;
	}

	/**
	 * Get id and iso639_3 language names for the given comma-separated
	 * list of iso639_3 language names.
	 */
	function getLanguages( $isoCodes ) {
		global $wgDBprefix;
		// create query to look up the language codes.
		$langQuery = "SELECT language_id, iso639_3 FROM {$wgDBprefix}language WHERE ";
		foreach ( $isoCodes as $isoCode ) {
			$isoCode = trim( $isoCode );
			// if query does not end in WHERE , prepend OR.
			if ( strpos( $langQuery, "WHERE " ) + 6 < strlen( $langQuery ) ) {
				$langQuery .= " OR ";
			}
			$langQuery .= "iso639_3='$isoCode'";
		}
		// Order by id so we can order the definitions and translations the same way.
		$langQuery .= " ORDER BY language_id";

		// wfDebug($langQuery."\n");

		$languages = [];
		$dbr = wfGetDB( DB_REPLICA );
		$langResults = $dbr->query( $langQuery );
		while ( $row = $langResults->fetchRow() ) {
			$languages[] = $row;
		}

		return $languages;
	}

	function createIsoLookup( $languages ) {
		$lookup = [];
		foreach ( $languages as $language ) {
			$lookup['id' . $language['language_id']] = $language['iso639_3'];
		}
		return $lookup;
	}

	/**
	 * Create the file name based on the languages requested.
	 * Change file name prefix and suffix here.
	 */
	function createFileName( $isoCodes ) {
		$fileName = "destit_";
		for ( $i = 0; $i < count( $isoCodes ); $i++ ) {
			$isoCode = $isoCodes[$i];
			if ( $i > 0 ) {
				$fileName .= '-';
			}
			$fileName .= $isoCode;
		}
		$fileName .= ".txt";
		return $fileName;
	}

	protected function getGroupName() {
		return 'omegawiki';	// message 'specialpages-group-omegawiki'
	}
}
