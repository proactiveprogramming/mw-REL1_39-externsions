<?php

use MediaWiki\Shell\Shell;
use Wikimedia\StaticArrayWriter;

$IP = getenv( 'MW_INSTALL_PATH' ) ?: __DIR__ . '/../../..';

require_once "$IP/maintenance/Maintenance.php";

class UpdateThemes extends Maintenance {
	public function __construct() {
		parent::__construct();

		$this->requireExtension( 'SyntaxHighlight' );
        $this->requireExtension( 'SyntaxHighlightThemes' );
		$this->addDescription( 'Update list of themes supported by SyntaxHighlightThemes and generate the coresponding CSS code' );
	}

	public function execute() {
		$header = 'Generated by ' . basename( __FILE__ );

		$themes = [];

		$result = Shell::command(
			SyntaxHighlight::getPygmentizePath(),
			'-L', 'style'
		)
			->restrict( Shell::RESTRICT_DEFAULT | Shell::NO_NETWORK )
			->execute();

		if ( $result->getExitCode() != 0 ) {
			$this->fatalError( 'Non-zero exit code: ' . $result->getStderr() );
		}

		$output = $result->getStdout();
		foreach ( explode( "\n", $output ) as $line ) {
			if ( substr( $line, 0, 1 ) === '*' ) {
				$theme = trim( $line, "* :\n" );
				// Exclude default theme since we will be adding it to beginning manually.
				if ( $theme != 'default' ) {
					$themes[$theme] = $theme;
				}
			}
		}

		// Sort the themes, but put default at the beginning.
		$themes = array_unique( $themes );
		ksort( $themes );
		$themes = ['default' => 'default'] + $themes;

		$writer = new StaticArrayWriter();
		$code = $writer->create( $themes, $header );

		file_put_contents( __DIR__ . '/../SyntaxHighlight.themes.php', $code );
		$this->output( "Updated themes list written to SyntaxHighlight.themes.php\n" );

		foreach ( $themes as $theme ) {
			$this->generateCSSFile($theme);
		}
	}

	protected function generateCSSFile( $theme ) {
		$target = __DIR__ . '/../modules/pygments.' . $theme . '.css';
		// Some Pygments themes rely on the default html style being black text on white background. 
		// Explicitly add this to ensure light-themes work on top of dark-theme skins.
		$css = "/* Stylesheet generated by updateThemes.php */\n" . 
			"pre, code { color: #000000; background-color: #ffffff }\n";

		$result = Shell::command(
			SyntaxHighlight::getPygmentizePath(),
			'-f', 'html',
			'-S', $theme,
			'-a', '.' . SyntaxHighlight::HIGHLIGHT_CSS_CLASS
		)
			->restrict( Shell::RESTRICT_DEFAULT | Shell::NO_NETWORK )
			->execute();

		if ( $result->getExitCode() != 0 ) {
			$this->fatalError( 'Non-zero exit code: ' . $result->getStderr() );
		}

		$css .= $result->getStdout();

		// Copy the styles for mw-highlight to <pre> and <code>.
		$css = preg_replace_callback(
			'/\.mw-highlight\s*{\s*(.*)\s*}/isU',
			function ( $matches ) {
				return ".mw-highlight { " . $matches[1] . " }\npre, code { " . $matches[1] . "; background-clip: padding-box }";
			},
			$css
		);


		if ( file_put_contents( $target, $css ) === false ) {
			$this->output( "Failed to write to {$target}\n" );
		} else {
			$this->output( 'CSS written to ' . realpath( $target ) . "\n" );
		}
	}
}

$maintClass = UpdateThemes::class;
require_once RUN_MAINTENANCE_IF_MAIN;