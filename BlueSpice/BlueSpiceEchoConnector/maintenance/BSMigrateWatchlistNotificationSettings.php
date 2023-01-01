<?php

use MediaWiki\MediaWikiServices;

$extDir = dirname( dirname( __DIR__ ) );

require_once "$extDir/BlueSpiceFoundation/maintenance/BSMaintenance.php";

class BSMigrateWatchlistNotificationSettings extends LoggedUpdateMaintenance {

	/**
	 *
	 * @return bool
	 */
	protected function doDBUpdates() {
		$this->output( "...bs_echoconnector -> watchlist preferences migration " );
		foreach ( $this->getUsers() as $user ) {
			$this->applyEchoPreferences( $user );
		}
		$this->deleteWatchlistPreferences();
		$this->output( "\n" );
		return true;
	}

	/**
	 * @return User[]
	 */
	private function getUsers(): array {
		$res = $this->getDB( DB_REPLICA )->select(
			'user_properties',
			'distinct up_user',
			[ 'up_property' => [
				'echo-subscriptions-email-watchlist',
				'echo-subscriptions-email-minor-watchlist',
				'enotifwatchlistpages',
				'enotifminoredits',
			] ],
			__METHOD__
		);
		$users = [];
		$userFactory = MediaWikiServices::getInstance()->getUserFactory();
		foreach ( $res as $row ) {
			$user = $userFactory->newFromId( $row->up_user );
			if ( !$user || $user->isAnon() ) {
				continue;
			}
			$users[] = $user;
		}
		return $users;
	}

	/**
	 * @return bool
	 */
	private function deleteWatchlistPreferences(): bool {
		return $this->getDB( DB_REPLICA )->delete(
			'user_properties',
			[ 'up_property' => [
				'echo-subscriptions-email-watchlist',
				'echo-subscriptions-email-minor-watchlist',
				'enotifwatchlistpages',
				'enotifminoredits',
			] ],
			__METHOD__
		);
	}

	/**
	 *
	 * @return string
	 */
	protected function getUpdateKey() {
		return 'bs_echoconnector-watchlist-pref-migration';
	}

	/**
	 * @param User $user
	 */
	public function applyEchoPreferences( User $user ) {
		foreach ( [
			'echo-subscriptions-email-bs-page-actions-cat',
			'echo-subscriptions-web-bs-page-actions-cat'
		] as $pref ) {
			error_log( var_export( [ 'up_property' => $pref, 'up_user' => $user->getId() ], 1 ) );
			$res = $this->getDB( DB_REPLICA )->selectRow(
				'user_properties',
				'up_value',
				[ 'up_property' => $pref, 'up_user' => $user->getId() ],
				__METHOD__
			);
			if ( $res ) {
				if ( $res->up_value < 0 ) {
					$this->output( "." );
					continue;
				}
				$success = $this->getDB( DB_REPLICA )->update(
					'user_properties',
					[ 'up_value' => 1 ],
					[ 'up_property' => $pref, 'up_user' => $user->getId() ],
					__METHOD__
				);
				$success ? $this->output( "." ) : $this->output( "f" );
				continue;
			}
			$success = $this->getDB( DB_REPLICA )->insert(
				'user_properties',
				[ 'up_value' => 1, 'up_property' => $pref, 'up_user' => $user->getId() ],
				__METHOD__
			);
			$success ? $this->output( "." ) : $this->output( "f" );
		}
	}

}
