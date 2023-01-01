<?php
declare( strict_types=1 );

namespace MediaWiki\Extension\WikibaseStatementUpdater\Parser;

use MediaWiki\Extension\WikibaseStatementUpdater\Batch\BatchItem;

/**
 * @author Niklas Laxström
 * @license GPL-2.0-or-later
 */
class V1Parser {
	/** @return BatchItem[] */
	public function parse( string $input ): array {
		$separator = "\r\n";
		$line = strtok( $input, $separator );
		$lineNumber = 0;

		$items = [];

		while ( $line !== false ) {
			$lineNumber++;
			if ( $line === '' ) {
				continue;
			}

			$parts = explode( "\t", $line, 4 );
			if ( count( $parts ) < 3 ) {
				throw new LineTooShort( $lineNumber, $line );
			} elseif ( count( $parts ) > 3 ) {
				throw new QualifiersUnsupported( $lineNumber, $line );
			}

			[ $subject, $commandProperty, $value ] = $parts;
			if ( !preg_match( '/^Q\d+$/', $subject ) ) {
				throw new OnlyQItemsSupported( $lineNumber, $line );
			}

			if ( preg_match( '/^P\d+$/', $commandProperty ) ) {
				$command = 'P';
				$commandId = $commandProperty;
			} elseif ( preg_match( '/^([LADS])(.+)$/', $commandProperty, $m ) ) {
				// TODO: $command = $m[0];
				// TODO: $commandId = $m[1];
				throw new UnsupportedCommand( $lineNumber, $line );
			} else {
				// @phan-suppress-previous-line PhanPluginDuplicateIfStatements
				throw new UnsupportedCommand( $lineNumber, $line );
			}

			$items[] = new BatchItem( $subject, $command, $commandId, $value );

			$line = strtok( $separator );
		}

		return $items;
	}
}
