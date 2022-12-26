<?php
declare( strict_types=1 );

namespace MediaWiki\Extension\WikibaseStatementUpdater\Updater;

/**
 * @author Niklas Laxström
 * @license GPL-2.0-or-later
 */
class AmbiguousUpdate extends UpdateFailure {
	public function getI18nMessage(): array {
		return [ 'wsu-updater-ambiguous-update' ];
	}
}
