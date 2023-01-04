<?php
namespace MediaWiki\Extension\SyntaxHighlightPages;

class Content extends \TextContent {
	// Must match the name used in the 'ContentHandlers' section of extension.json
	const MODEL = 'syntaxhighlight';

	public function __construct( $text, $model_id = self::MODEL ) {
		parent::__construct( $text, $model_id );
	}

	public function getExtensionMap() {
		// Turn e.g, [ 'foo', 'bar' => 'foo' ] to [ 'foo' => 'foo', 'bar' => 'foo' ]
		//
		// Need to do this transform as relying on keys instead (e.g. checking
		// isset(wgSyntaxHighlightPagesSuffixes[$ext])) gives a surprising
		// positive for e.g. 'Foo.0' because PHP casts the '0' to 0, which is a
		// valid key if there are any non-mappped values.
		//
		// Also prevents [ 'foo' => 'bar' ] from enabling 'bar' as an extension
		// which may not be desired.
		global $wgSyntaxHighlightPagesSuffixes;
		$map = array();
		foreach ($wgSyntaxHighlightPagesSuffixes as $k => $v) {
			$map[gettype($k) === "integer" ? $v : $k] = $v;
		}
		return $map;
	}

	protected function fillParserOutput(
		\Title $title, $revId, \ParserOptions $options, $generateHtml, \ParserOutput &$output
	){
		$parts = explode('.', $title->getDBkey());
		$ext = end($parts);
		$map = Content::getExtensionMap();
		$lang = isset($map[$ext]) ? $map[$ext] : "";
		$status = \SyntaxHighlight::highlight( $this->mText, $lang );
		if ( !$status->isOK() ) {
			return true;
		}

		$output->addModuleStyles( 'ext.pygments' );
		$output->setText( '<div dir="ltr">' . $status->getValue() . '</div>' );
	}
}

