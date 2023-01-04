<?php

use MediaWiki\MediaWikiServices;

class OnlineStatus {
	/**
	 * Get the user online status
	 *
	 * @param mixed $title string of Title object, if it's a title, if has to be in
	 *                     User: of User_talk: namespace.
	 * @param bool $checkShowPref
	 * @return array ( string status, string username 	) or null
	 */
	static function GetUserStatus( $title, $checkShowPref = false ) {
		if ( is_object( $title ) ) {
			if ( !$title instanceof Title ) {
				return null;
			}

			if ( !in_array( $title->getNamespace(), [ NS_USER, NS_USER_TALK ] ) ) {
				return null;
			}

			$username = explode( '/', $title->getDBkey() );
			$username = $username[0];
		} else {
			$username = $title;
		}

		$user = User::newFromName( $username );

		if ( !$user instanceof User || $user->getId() == 0 ) {
			return null;
		}

		$services = MediaWikiServices::getInstance();
		if ( method_exists( $services, 'getUserOptionsManager' ) ) {
			// MW 1.35 +
			$userOptionsManager = $services->getUserOptionsManager();
			if ( $checkShowPref && !$userOptionsManager->getOption( $user, 'showonline' ) ) {
				return null;
			}

			return [ $userOptionsManager->getOption( $user, 'online' ), $username ];
		}

		if ( $checkShowPref && !$user->getOption( 'showonline' ) ) {
			return null;
		}

		return [ $user->getOption( 'online' ), $username ];
	}

	/**
	 * Used for AJAX requests
	 * @param string $action
	 * @param bool $stat
	 * @return string
	 */
	static function Ajax( $action, $stat = false ) {
		$user = RequestContext::getMain()->getUser();

		if ( $user->isAnon() ) {
			return wfMessage( 'onlinestatus-js-anon' )->escaped();
		}

		switch ( $action ) {
		case 'get':
			$services = MediaWikiServices::getInstance();
			if ( method_exists( $services, 'getUserOptionsManager' ) ) {
				// MW 1.35 +
				$userOptionsManager = $services->getUserOptionsManager();
				$def = $userOptionsManager->getOption( $user, 'online' );
			} else {
				$def = $user->getOption( 'online' );
			}
			$msg = wfMessage( 'onlinestatus-levels' )->inContentLanguage()->plain();
			$lines = explode( "\n", $msg );
			$radios = [];

			foreach ( $lines as $line ) {
				if ( substr( $line, 0, 1 ) != '*' ) {
					continue;
				}

				// For grep. Message keys used here:
				// onlinestatus-toggle-offline, onlinestatus-toggle-online
				$lev = trim( $line, '* ' );
				$radios[] = [
					$lev,
					wfMessage( 'onlinestatus-toggle-' . $lev )->text(),
					$lev == $def
				];
			}

			return json_encode( $radios );
		case 'set':
			if ( $stat ) {
				$dbw = wfGetDB( DB_MASTER );
				$dbw->startAtomic( __METHOD__ );

				$services = MediaWikiServices::getInstance();
				if ( method_exists( $services, 'getUserOptionsManager' ) ) {
					// MW 1.35 +
					$userOptionsManager = $services->getUserOptionsManager();
					$actual = $userOptionsManager->getOption( $user, 'online' );
					$userOptionsManager->setOption( $user, 'online', $stat );
					if ( $actual != $stat ) {
						$user->getUserPage()->invalidateCache();
						$user->getTalkPage()->invalidateCache();
					}
					$userOptionsManager->saveOptions( $user );
				} else {
					$actual = $user->getOption( 'online' );
					$user->setOption( 'online', $stat );
					if ( $actual != $stat ) {
						$user->getUserPage()->invalidateCache();
						$user->getTalkPage()->invalidateCache();
					}
					$user->saveSettings();
				}

				$user->invalidateCache();
				$dbw->endAtomic( __METHOD__ );

				// For grep. Message keys used here:
				// onlinestatus-toggle-offline, onlinestatus-toggle-online
				return wfMessage(
					'onlinestatus-js-changed',
					wfMessage( 'onlinestatus-toggle-' . $stat )->escaped()
				)->text();
			} else {
				return wfMessage( 'onlinestatus-js-error', $stat )->escaped();
			}
		}
	}

	/**
	 * Hook for ParserFirstCallInit
	 * @param Parser $parser
	 * @return true
	 */
	static function ParserFirstCallInit( $parser ) {
		global $wgAllowAnyUserOnlineStatusFunction;

		if ( $wgAllowAnyUserOnlineStatusFunction ) {
			$parser->setFunctionHook( 'anyuseronlinestatus', [ __CLASS__, 'ParserHookCallback' ] );
		}
		return true;
	}

	/**
	 * Callback for {{#anyuserstatus:}}
	 * @param Parser &$parser
	 * @param User $user
	 * @param bool $raw
	 * @return array|string
	 */
	static function ParserHookCallback( &$parser, $user, $raw = false ) {
		$status = self::GetUserStatus( $user );

		if ( $status === null ) {
			return [ 'found' => false ];
		}

		if ( empty( $raw ) ) {
			// For grep. Message keys used here:
			// onlinestatus-toggle-offline, onlinestatus-toggle-online
			return wfMessage( 'onlinestatus-toggle-' . $status[0] )->plain();
		} else {
			return $status[0];
		}
	}

	/**
	 * Hook function for MagicWordwgVariableIDs
	 * @param array &$magicWords
	 * @return true
	 */
	static function MagicWordVariable( &$magicWords ) {
		$magicWords[] = 'onlinestatus_word';
		$magicWords[] = 'onlinestatus_word_raw';

		return true;
	}

	/**
	 * Hook function for ParserGetVariableValueSwitch
	 * @param Parser &$parser
	 * @param array &$varCache
	 * @param string &$index
	 * @param array &$ret
	 * @return true
	 */
	static function ParserGetVariable( &$parser, &$varCache, &$index, &$ret ) {
		if ( $index == 'onlinestatus_word' ) {
			$status = self::GetUserStatus( $parser->getTitle() );

			if ( $status === null ) {
				return true;
			}

			// For grep. Message keys used here:
			// onlinestatus-toggle-offline, onlinestatus-toggle-online
			$ret = wfMessage( 'onlinestatus-toggle-' . $status[0] )->plain();
			$varCache['onlinestatus'] = $ret;
		} elseif ( $index == 'onlinestatus_word_raw' ) {
			$status = self::GetUserStatus( $parser->getTitle() );

			if ( $status === null ) {
				return true;
			}

			$ret = $status[0];
			$varCache['onlinestatus'] = $ret;
		}

		return true;
	}

	/**
	 * Hook for user preferences
	 * @param User $user
	 * @param array &$preferences
	 * @return true
	 */
	public static function GetPreferences( $user, &$preferences ) {
		$msg = wfMessage( 'onlinestatus-levels' )->inContentLanguage()->plain();
		$lines = explode( "\n", $msg );
		$radios = [];

		foreach ( $lines as $line ) {
			if ( substr( $line, 0, 1 ) != '*' ) {
				continue;
			}

			// For grep. Message keys used here:
			// onlinestatus-toggle-offline, onlinestatus-toggle-online
			$lev = trim( $line, '* ' );
			$radios[wfMessage( 'onlinestatus-toggle-' . $lev )->text()] = $lev;
		}

		$preferences['onlineonlogin'] =
			[
				'type' => 'toggle',
				'section' => 'misc',
				'label-message' => 'onlinestatus-pref-onlineonlogin',
			];

		$preferences['offlineonlogout'] =
			[
				'type' => 'toggle',
				'section' => 'misc',
				'label-message' => 'onlinestatus-pref-offlineonlogout',
			];

		$prefs = [
			'online' => [
				'type' => 'radio',
				'section' => 'personal/info',
				'options' => $radios,
				'label-message' => 'onlinestatus-toggles-desc',
			],
			'showonline' => [
				'type' => 'check',
				'section' => 'personal/info',
				'label-message' => 'onlinestatus-toggles-show',
				'help-message' => 'onlinestatus-toggles-explain',
			]
		];

		$after = array_key_exists( 'registrationdate', $preferences ) ? 'registrationdate' : 'editcount';
		$preferences = wfArrayInsertAfter( $preferences, $prefs, $after );

		return true;
	}

	/**
	 * Hook for UserLoginComplete
	 * @param User $user
	 * @return true
	 */
	static function UserLoginComplete( $user ) {
		$services = MediaWikiServices::getInstance();
		if ( method_exists( $services, 'getUserOptionsManager' ) ) {
			// MW 1.35 +
			$userOptionsManager = $services->getUserOptionsManager();
			if ( $userOptionsManager->getOption( $user, 'onlineonlogin' ) ) {
				$userOptionsManager->setOption( $user, 'online', 'online' );
				$userOptionsManager->saveOptions( $user );
			}
		} else {
			if ( $user->getOption( 'onlineonlogin' ) ) {
				$user->setOption( 'online', 'online' );
				$user->saveSettings();
			}
		}

		return true;
	}

	/**
	 * Hook for UserLoginComplete
	 * @param User &$newUser
	 * @param string &$injected_html
	 * @param string|null $oldName
	 * @return true
	 */
	static function UserLogoutComplete( &$newUser, &$injected_html, $oldName = null ) {
		if ( $oldName === null ) {
			return true;
		}

		$oldUser = User::newFromName( $oldName );

		if ( !$oldUser instanceof User ) {
			return true;
		}

		$services = MediaWikiServices::getInstance();
		if ( method_exists( $services, 'getUserOptionsManager' ) ) {
			// MW 1.35 +
			$userOptionsManager = $services->getUserOptionsManager();
			if ( $userOptionsManager->getOption( $oldUser, 'offlineonlogout' ) ) {
				$userOptionsManager->setOption( $oldUser, 'online', 'offline' );
				$userOptionsManager->saveOptions( $oldUser );
			}
		} else {
			if ( $oldUser->getOption( 'offlineonlogout' ) ) {
				$oldUser->setOption( 'online', 'offline' );
				$oldUser->saveSettings();
			}
		}

		return true;
	}

	/**
	 * Hook function for BeforePageDisplay
	 * @param OutputPage &$out
	 * @return true
	 */
	static function BeforePageDisplay( &$out ) {
		global $wgRequest, $wgUseAjax;

		$user = RequestContext::getMain()->getUser();
		if ( $user->isRegistered() && $wgUseAjax ) {
			$out->addModules( 'ext.onlineStatus' );
		}

		if ( !in_array( $wgRequest->getVal( 'action', 'view' ), [ 'view', 'purge' ] ) ) {
			return true;
		}

		$status = self::GetUserStatus( $out->getTitle(), true );

		if ( $status === null ) {
			return true;
		}

		// For grep. Message keys used here:
		// onlinestatus-subtitle-offline, onlinestatus-subtitle-online
		$out->setSubtitle(
			wfMessage(
				'onlinestatus-subtitle-' . $status[0],
				$status[1]
			)->parseAsBlock()
		);

		return true;
	}

	/**
	 * Hook for PersonalUrls
	 * @param array &$urls
	 * @param Title &$title
	 * @return true
	 */
	static function PersonalUrls( &$urls, &$title ) {
		global $wgUseAjax;

		# Require ajax
		$user = RequestContext::getMain()->getUser();
		if ( !$user->isRegistered() || !$wgUseAjax || $title->isSpecial( 'Preferences' ) ) {
			return true;
		}

		$arr = [];

		foreach ( $urls as $key => $val ) {
			if ( $key == 'logout' ) {
				$arr['status'] = [
					'text' => wfMessage( 'onlinestatus-tab' )->escaped(),
					'href' => 'javascript:;',
					'active' => false,
				];
			}

			$arr[$key] = $val;
		}

		$urls = $arr;
		return true;
	}
}
