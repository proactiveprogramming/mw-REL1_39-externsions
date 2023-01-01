<?php
namespace MediaWiki\Extension\NaylorAMS;

class ExtraLoginFields extends \ArrayObject {

	const USERNAME = 'username';
	const PASSWORD = 'password';
	// const AUTHKEY = 'authkey';

	public function __construct( $config ) {
		parent::__construct([
			static::USERNAME => [
				'type' => 'string',
				'label' => wfMessage( 'userlogin-yourname' ),
				'help' => wfMessage( 'authmanager-username-help' ),
			],
			static::PASSWORD => [
				'type' => 'password',
				'label' => wfMessage( 'userlogin-yourpassword' ),
				'help' => wfMessage( 'authmanager-password-help' ),
				'sensitive' => true,
			]
		]);
		// parent::__construct([
		//	static::AUTHKEY => [
		//		'type' => 'string',
		//		'label' => wfMessage( 'naylor-authkey' ),
		//		'help' => wfMessage( 'naylor-authkey-help' ),
		//	]
		// ]);
	}

}
