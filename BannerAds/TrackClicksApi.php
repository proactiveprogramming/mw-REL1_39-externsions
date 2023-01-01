<?php

class TrackClicksApi extends ApiBase {

	public function execute() {
		$request = $this->getMain()->getRequest();
		$params = $request->getValues();
		Hooks::run( 'RecordClick', array( $params ) );

		if ( !empty( $params['external_url'] ) ) {
			header( "Location:" . urldecode( $params['external_url'] ) );
			exit;
		}

		if ( !empty( $params['returntoext'] ) ) {
			header( "Location: " . $params['returntoext'] );
			exit;
		}
		$title = Title::newFromText( $params['returnto'] );

		$addl_params = '';
		if ( $params['addl_params'] ) {
			$addl_params = '?' . implode('&', explode('|', $params['addl_params']));
		}
		$url = $title->getFullURL();
		if ( $params['frag'] ) {
			$url .= '#' . $params['frag'];
		}
		$url .= $addl_params;

		header("Location:" . $url );
		exit;
	}

}
