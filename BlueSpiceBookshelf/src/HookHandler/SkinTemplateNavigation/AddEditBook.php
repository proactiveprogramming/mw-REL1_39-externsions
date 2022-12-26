<?php

namespace BlueSpice\Bookshelf\HookHandler\SkinTemplateNavigation;

use MediaWiki\Hook\SkinTemplateNavigation__UniversalHook;
use MediaWiki\MediaWikiServices;
use SkinTemplate;

class AddEditBook implements SkinTemplateNavigation__UniversalHook {

	/**
	 * @param SkinTemplate $sktemplate
	 * @return bool
	 */
	protected function skipProcessing( SkinTemplate $sktemplate ) {
		$title = $sktemplate->getTitle();

		if ( $title->getContentModel() !== 'book' ) {
			return true;
		}
		$user = $sktemplate->getUser();
		$permissionManager = MediaWikiServices::getInstance()->getPermissionManager();
		$userCan = $permissionManager->userCan( 'edit', $user, $title );
		if ( !$userCan ) {
			return true;
		}
		return false;
	}

	/**
	 * // phpcs:disable MediaWiki.NamingConventions.LowerCamelFunctionsName.FunctionName
	 * @inheritDoc
	 */
	public function onSkinTemplateNavigation__Universal( $sktemplate, &$links ): void {
		if ( $this->skipProcessing( $sktemplate ) ) {
			return;
		}

		$links['views']['edit'] = [
			'text' => $sktemplate->msg( 'edit' )->text(),
			'title' => $sktemplate->msg( 'edit' )->text(),
			'href' => $sktemplate->getTitle()->getLocalURL( [
				'action' => 'edit',
			] ),
			'id' => 'edit-book'
		];

		$links['views']['editbooksource'] = $links['views']['edit'];
		$links['views']['editbooksource']['id']	= 'editbooksource';
		$links['views']['editbooksource']['text']
			= $sktemplate->msg( 'bs-bookshelf-action-editbook' )->plain();
		$links['views']['editbooksource']['href'] = $sktemplate->getTitle()->getLinkURL( [
			'action' => 'editbooksource'
		] );
	}
}
