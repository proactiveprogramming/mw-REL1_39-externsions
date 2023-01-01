<?php

namespace BlueSpice\EchoConnector\HookHandler;

use MediaWiki\Hook\SkinTemplateNavigation__UniversalHook;
use MWEchoNotifUser;
use SpecialPage;

class Skin implements SkinTemplateNavigation__UniversalHook {

	/**
	 * // phpcs:disable MediaWiki.NamingConventions.LowerCamelFunctionsName.FunctionName
	 * @inheritDoc
	 */
	public function onSkinTemplateNavigation__Universal( $sktemplate, &$links ): void {
		if ( isset( $links['notifications-alert'] ) ) {
			if ( !isset( $links['notifications-alert']['data'] ) ) {
				$links['notifications-alert']['data'] = [];
			}
			$links['notifications-alert']['data']['attentionindicator']
				= 'notifications-alert';
			$links['notifications-alert']['position'] = 100;
		}
		if ( isset( $links['notifications-notice'] ) ) {
			if ( !isset( $links['notifications-notice']['data'] ) ) {
				$links['notifications-notice']['data'] = [];
			}
			$links['notifications-notice']['data']['attentionindicator']
				= 'notifications-notice';
			$links['notifications-notice']['position'] = 110;
		}
		if ( is_a( $sktemplate, 'BlueSpice\Discovery\Skin', true ) === false ) {
			return;
		}
		if ( $sktemplate->getUser()->isAnon() ) {
			return;
		}
		if ( isset( $links['notifications-alert'] ) ) {
			unset( $links['notifications-alert'] );
		}
		if ( isset( $links['notifications-notice'] ) ) {
			unset( $links['notifications-notice'] );
		}
		$notifUser = MWEchoNotifUser::newFromUser( $sktemplate->getUser() );
		$count = $notifUser->getAlertCount() + $notifUser->getMessageCount();
		$url = SpecialPage::getTitleFor( 'Notifications' )->getLocalURL();
		$msg = $sktemplate->msg( 'bs-echoconnector-personalurl-notifications' )->params( $count );
		$links['user-menu']['bsec-notifications'] = [
			'id' => 'pt-bsec-notifications',
			'href' => $url,
			'text' => $msg->text(),
			'active' => $url == $sktemplate->getTitle()->getLocalURL(),
			'data' => [
				'counter-num' => $count,
				'counter-text' => $msg->text(),
				'attentionindicator' => 'notifications',
			],
			'position' => 100,
		];
	}

}
