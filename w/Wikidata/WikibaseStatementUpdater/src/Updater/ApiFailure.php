<?php
declare( strict_types=1 );

namespace MediaWiki\Extension\WikibaseStatementUpdater\Updater;

/**
 * @author Niklas Laxström
 * @license GPL-2.0-or-later
 */
class ApiFailure extends UpdateFailure {
	public function getI18nMessage(): array {
		return [ 'wsu-updater-api-failure' ];
	}
}
