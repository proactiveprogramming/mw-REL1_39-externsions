<?php

namespace MediaWiki\Extension\Math\TexVC\MMLmappings\Util;

/**
 * This class contains functions to generate a
 * HTML File which shows the formula converted to MathML
 * by TexVC
 * @author Johannes Stegmüller
 */
class MMLTestUtilHTML {

	public static function generateHTMLtableItem( $input, $bold = false ) {
		if ( !$bold ) {
			return "<th class=\"tg-0lax\">" . $input . "</th>";
		} else {
			return "<th class=\"tg-0lax\">" . "<b>" . $input . "</b>" . "</th>";
		}
	}

	public static function generateHTMLEnd( $filePath, $active = true ) {
		if ( !$active ) {
			return;
		}
		$file = fopen( $filePath, 'a' );
		$stringData = "</thead>
                </table>";
		fwrite( $file, $stringData );
		fclose( $file );
	}

	public static function generateHTMLtableRow( $filePath, $id, $inputTex, $mmlMj3, $mmlGen,
												 $bold = false, $active = true ) {
		if ( !$active ) {
			return;
		}
		$file = fopen( $filePath, 'a' );

		$stringData = "<tr>"
			. self::generateHTMLtableItem( $id, $bold )
			. self::generateHTMLtableItem( $inputTex, $bold )
			. self::generateHTMLtableItem( $mmlMj3, $bold )
			. self::generateHTMLtableItem( $mmlGen, $bold ) .
			"</tr>";

		fwrite( $file, $stringData );

		fclose( $file ); // tbd only open close once for all tests
	}

	public static function generateHTMLstart( $filePath, $name, $active = true ) {
		if ( !$active ) {
			return;
		}
		$file = fopen( $filePath, 'w' ); // or die("error");
		$stringData = "<style type=\"text/css\">
                .tg  {border-collapse:collapse;border-spacing:0;}
                .tg td{border-color:black;border-style:solid;border-width:1px;font-family:Arial," .
				" sans-serif;font-size:14px;
                  overflow:hidden;padding:10px 5px;word-break:normal;}
                .tg th{border-color:black;border-style:solid;border-width:1px;font-family:Arial," .
				" sans-serif;font-size:14px;
                  font-weight:normal;overflow:hidden;padding:10px 5px;word-break:normal;}
                .tg .tg-0lax{text-align:left;vertical-align:top}
                </style>
                <table class=\"tg\">
                <thead>";
		fwrite( $file, $stringData );
		fclose( $file );
		self::generateHTMLtableRow( $filePath, "-", "Tex-Input", $name, "MathML(TexVC)", true );
	}
}
