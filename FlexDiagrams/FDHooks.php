<?php
/**
 * Static functions called by various outside hooks, as well as by
 * extension.json.
 *
 * @author Yaron Koren
 * @file
 * @ingroup FlexDiagrams
 */

class FDHooks {

	public static function registerExtension() {
		define( 'CONTENT_MODEL_FD_BPMN', 'flexdiagrams-bpmn' );
		define( 'CONTENT_MODEL_FD_GANTT', 'flexdiagrams-gantt' );
		define( 'CONTENT_MODEL_FD_DRAWIO', 'flexdiagrams-drawio' );
		define( 'CONTENT_MODEL_FD_MERMAID', 'flexdiagrams-mermaid' );
	}

	/**
	 * Register the namespaces for Flex Diagrams.
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/CanonicalNamespaces
	 *
	 * @param array &$list
	 *
	 * @return true
	 */
	public static function registerNamespaces( array &$list ) {
		if ( !defined( 'FD_NS_BPMN' ) ) {
			define( 'FD_NS_BPMN', 740 );
			define( 'FD_NS_BPMN_TALK', 741 );
		}
		if ( !defined( 'FD_NS_GANTT' ) ) {
			define( 'FD_NS_GANTT', 742 );
			define( 'FD_NS_GANTT_TALK', 743 );
		}
		if ( !defined( 'FD_NS_MERMAID' ) ) {
			define( 'FD_NS_MERMAID', 744 );
			define( 'FD_NS_MERMAID_TALK', 745 );
		}
		if ( !defined( 'FD_NS_DRAWIO' ) ) {
			define( 'FD_NS_DRAWIO', 746 );
			define( 'FD_NS_DRAWIO_TALK', 747 );
		}

		$list[FD_NS_BPMN] = 'BPMN';
		$list[FD_NS_BPMN_TALK] = 'BPMN_talk';
		$list[FD_NS_GANTT] = 'Gantt';
		$list[FD_NS_GANTT_TALK] = 'Gantt_talk';
		$list[FD_NS_DRAWIO] = 'Drawio';
		$list[FD_NS_DRAWIO_TALK] = 'Drawio_talk';
		$list[FD_NS_MERMAID] = 'Mermaid';
		$list[FD_NS_MERMAID_TALK] = 'Mermaid_talk';

		return true;
	}

	public static function registerParserFunctions( &$parser ) {
		$parser->setFunctionHook( 'display_diagram', [ 'FDDisplayDiagram', 'run' ] );
		return true;
	}

	static function setGlobalJSVariables( &$vars ) {
		global $wgServer, $wgScript;

		$vars['wgServer'] = $wgServer;
		$vars['wgScript'] = $wgScript;

		return true;
	}

	public static function disableParserCache( Parser &$parser, string &$text ) {
		$title = $parser->getTitle();
		$ns = $title->getNamespace();
		if ( $ns == FD_NS_BPMN || $ns == FD_NS_GANTT || $ns == FD_NS_MERMAID ) {
			$parser->getOutput()->updateCacheExpiry( 0 );
		}
	}

	/**
	 * Called by the HtmlPageLinkRendererEnd hook.
	 *
	 * Point red links to any diagram pages to "action=editdiagram".
	 *
	 * @param LinkRenderer $linkRenderer
	 * @param Title $target
	 * @param bool $isKnown
	 * @param string &$text
	 * @param array &$attribs
	 * @param bool &$ret
	 * @return true
	 */
	static function linkToEditDiagramAction( MediaWiki\Linker\LinkRenderer $linkRenderer, $target, $isKnown,
		&$text, &$attribs, &$ret ) {
		global $wgFlexDiagramsEnabledFormats;

		// If it's not a broken (red) link, exit.
		if ( $isKnown ) {
			return true;
		}
		$namespace = $target->getNamespace();
		if ( !in_array( $namespace, $wgFlexDiagramsEnabledFormats ) ) {
			return true;
		}

		// The class of $target can be either Title or TitleValue.
		$title = Title::newFromLinkTarget( $target );
		$attribs['href'] = $title->getLinkURL( [ 'action' => 'editdiagram', 'redlink' => '1' ] );

		return true;
	}

}
