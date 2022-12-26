<?php
/**
 * FDDisplayDiagram - class for the #display_diagram parser function.
 *
 * @author Yaron Koren
 * @ingroup FlexDiagrams
 */

use MediaWiki\MediaWikiServices;
use MediaWiki\Revision\SlotRecord;

class FDDisplayDiagram {

	/** @var int The number of instances of #display_diagram on this page */
	private static $numInstances = 0;

	/**
	 * Handles the #display_diagram parser function - displays the
	 * diagram defined in the specified page.
	 *
	 * @param Parser &$parser
	 * @return string
	 */
	public static function run( &$parser ) {
		$params = func_get_args();
		// we already know the $parser...
		array_shift( $params );

		$parser->getOutput()->updateCacheExpiry( 0 );

		$diagramPageName = $params[0];

		$diagramPage = Title::newFromText( $diagramPageName );
		if ( !$diagramPage->exists() ) {
			return '<div class="error">' . "Page [[$diagramPage]] does not exist." . '</div>';
		}

		if ( $diagramPage->getNamespace() == FD_NS_BPMN || $diagramPage->getNamespace() == FD_NS_GANTT
		|| $diagramPage->getNamespace() == FD_NS_DRAWIO ) {
			if ( self::$numInstances++ > 0 ) {
				return '<div class="error">Due to current limitations, #display_diagram can only ' .
					'be called once per page on any BPMN or Gantt diagram.</div>';
			}
		}

		if ( $diagramPage->getNamespace() == FD_NS_BPMN ) {
			global $wgOut;
			$wgOut->addModules( 'ext.flexdiagrams.bpmn.viewer' );
			$text = Html::element( 'div', [
				'id' => 'canvas',
				'data-wiki-page' => $diagramPageName
			], ' ' );
		} elseif ( $diagramPage->getNamespace() == FD_NS_GANTT ) {
			global $wgOut;
			$wgOut->addModules( 'ext.flexdiagrams.gantt' );
			$text = Html::element( 'div', [
				'id' => 'canvas',
				'data-wiki-page' => $diagramPageName
			], ' ' );
		} elseif ( $diagramPage->getNamespace() == FD_NS_DRAWIO ) {
			global $wgOut;
			$wgOut->addModules( 'ext.flexdiagrams.drawio' );
			$text = Html::element( 'div', [
				'id' => 'canvas',
				'data-wiki-page' => $diagramPageName
			], ' ' );
		} elseif ( $diagramPage->getNamespace() == FD_NS_MERMAID ) {
			global $wgOut, $wgResourceLoaderDebug;
			$wgResourceLoaderDebug = true;
			$wgOut->addModules( 'ext.flexdiagrams.mermaid' );
			$revisionRecord = MediaWikiServices::getInstance()
				->getRevisionLookup()
				->getRevisionByTitle( $diagramPage );
			$mermaidText = $revisionRecord->getContent( SlotRecord::MAIN )->getNativeData();
			$text = Html::rawElement( 'div', [
				'class' => 'mermaid'
			], "<nowiki>$mermaidText</nowiki>" );
			return [ $text, 'noparse' => false, 'isHTML' => false ];
		} else {
			$text = '<div class="error">Invalid namespace for a diagram page.</div>';
		}

		return [ $text, 'noparse' => true, 'isHTML' => true ];
	}

}
