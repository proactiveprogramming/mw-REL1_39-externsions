<?php
/**
 * Class that can export applications and reviews into doc/pdf.
 *
 * @file
 * @author Niklas Laxström
 * @license GPL-2.0-or-later
 */

namespace Ainut;

use Exception;
use IContextSource;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Settings;
use User;

class DocumentExporter {
	public function createDocument( $appReviews, IContextSource $context ) {
		$doc = new PhpWord();
		$isSummary = count( $appReviews ) > 1;

		$properties = $doc->getDocInfo();
		$properties->setCreator( 'Extension:Ainut' );

		$doc->addTitleStyle(
			1,
			[ 'name' => 'Helvetica Neue', 'size' => 25, 'bold' => true ],
			[ 'spaceBefore' => 300, 'spaceAfter' => 100 ]
		);
		$doc->addTitleStyle(
			2,
			[ 'name' => 'Helvetica Neue', 'size' => 15 ],
			[ 'spaceBefore' => 150, 'spaceAfter' => 50 ]
		);

		$doc->addTitleStyle(
			3,
			[ 'name' => 'Helvetica Neue', 'size' => 12, 'underline' => true ],
			[ 'spaceBefore' => 150, 'spaceAfter' => 50 ]
		);

		$doc->setDefaultFontName( 'Garamond' );
		$doc->setDefaultFontSize( 10 );

		$section = $doc->addSection();
		if ( $isSummary ) {
			$section->addTOC( [], [], 1, 2 );
		}

		foreach ( $appReviews as $app ) {
			$section->addTitle( htmlspecialchars( $app->getFields()['title'] ), 1 );
			foreach ( $app->getFields() as $name => $value ) {
				if ( $name === 'title' ) {
					continue;
				}
				$titleText = trim( strip_tags( $context->msg( "ainut-app-$name" )->parse() ) );
				$section->addTitle( $titleText, 3 );
				if ( is_array( $value ) ) {
					foreach ( $value as $item ) {
						if ( $name === 'categories' ) {
							$item = $context->msg( $item )->plain();
						}
						$section->addListItem( htmlspecialchars( $item ) );
					}
				} else {
					$paras = preg_split( '/\R/u', $value, null, PREG_SPLIT_NO_EMPTY );
					foreach ( $paras as $para ) {
						$section->addText( htmlspecialchars( $para ) );
					}
				}
			}

			foreach ( $appReviews[$app] as $review ) {
				$name = User::newFromId( $review->getUser() )->getName();
				$section->addTitle( "Käyttäjän $name arvio", 2 );
				$section->addText( $review->getFields()['review'] );
			}
		}

		return $doc;
	}

	public function printDocument( $doc, $name, $format = 'Word2007' ) {
		switch ( $format ) {
			case 'Word2007':
				$ext = 'docx';
				break;
			case 'PDF':
				// Seriously. USE AUTOLOADING!
				Settings::setPdfRenderer( Settings::PDF_RENDERER_DOMPDF, __DIR__ );
				$ext = 'pdf';
				break;
			default:
				throw new Exception( 'Invalid format' );
		}

		header( 'Content-Description: File Transfer' );
		header( "Content-Disposition: attachment; filename=\"$name.$ext\"" );
		header(
			'Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document'
		);
		header( 'Content-Transfer-Encoding: binary' );
		header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
		header( 'Expires: 0' );
		$writer = IOFactory::createWriter( $doc, $format );
		$writer->save( 'php://output' );
	}
}
