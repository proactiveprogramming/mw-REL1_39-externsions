<?php

namespace BlueSpice\EchoConnector\Hook\GetPreferences;

use BlueSpice\Hook\GetPreferences;

class HidePreferences extends GetPreferences {

	protected function doProcess() {
		foreach ( [ 'enotifwatchlistpages','enotifminoredits' ] as $watchPrefs ) {
			if ( !isset( $this->preferences[$watchPrefs] ) ) {
				continue;
			}
			$this->preferences[$watchPrefs]['type'] = 'hidden';
		}
		if ( !isset( $this->preferences['echo-subscriptions'] ) ) {
			return;
		}
		$prefs = [
			'email-watchlist',
			'email-minor-watchlist',
			'web-watchlist',
			'web-minor-watchlist',
		];
		foreach ( $this->preferences['echo-subscriptions']['rows'] as $label => $key ) {
			if ( !in_array( $key, $prefs ) ) {
				continue;
			}
			unset( $this->preferences['echo-subscriptions']['rows'][$label] );
			if ( !isset( $this->preferences['echo-subscriptions']['tooltips'][$label] ) ) {
				continue;
			}
			unset( $this->preferences['echo-subscriptions']['tooltips'][$label] );
		}
	}

}
