<?php

namespace MediaWiki\Extension\StandardDialogs\HookHandler;

use MediaWiki\Hook\SkinTemplateNavigation__UniversalHook;
use MediaWiki\Permissions\PermissionManager;
use SkinTemplate;
use Title;

class Skin implements SkinTemplateNavigation__UniversalHook {

	/**
	 *
	 * @var PermissionManager
	 */
	private $permissionManager = null;

	/**
	 *
	 * @param PermissionManager $permissionManager
	 */
	public function __construct( PermissionManager $permissionManager ) {
		$this->permissionManager = $permissionManager;
	}

	/**
	 *
	 * @param SkinTemplate $sktemplate
	 * @param array &$links
	 * @return void
	 */
	public function onSkinTemplateNavigation__Universal( $sktemplate, &$links ): void {
		/**
		 * Unfortunately the `VectorTemplateTest::testGetMenuProps` from `Skin:Vector` will break
		 * in `REL1_35`, as it does not properly clear out all hook handlers.
		 * See https://github.com/wikimedia/Vector/blob/1b03bafb1267f350ee2b0018da53c31ee0674f92/tests/phpunit/integration/VectorTemplateTest.php#L107-L108
		 * In later versions this test does not exist anymore and we can remove the bail out again.
		 * We do not perform any own UnitTests on this class, so bailing out here should be fine.
		 */
		if ( defined( 'MW_PHPUNIT_TEST' ) ) {
			return;
		}

		$user = $sktemplate->getSkin()->getUser();
		$title = $sktemplate->getSkin()->getRelevantTitle();
		if ( $title instanceof Title === false ) {
			return;
		}

		if ( !$this->permissionManager->userHasRight( $user, 'edit' ) ) {
			return;
		}

		if ( $title->isContentPage() && $title->exists() ) {
			$links['actions']['copy'] = [
				"href" => '',
				"text" => $sktemplate->msg( 'standarddialogs-copy-page-legend' )->text(),
				"title" => $sktemplate->msg( 'standarddialogs-copy-page-legend' )->text(),
				'position' => 20,
			];
		}

		if ( $this->permissionManager->userHasRight( $user, 'createpage' ) ) {
			$links['namespaces']['new-page'] = [
				'text' => $sktemplate->msg( 'standarddialogs-create-button-new-page-text' ),
				'title' => $sktemplate->msg( 'standarddialogs-create-button-new-page-title' ),
				'href' => '',
				'class' => 'new-page'
			];
		}

		if ( $this->permissionManager->userHasRight( $user, 'createpage' ) ) {
			$links['namespaces']['new-subpage'] = [
				'text' => $sktemplate->msg( 'standarddialogs-create-button-new-subpage-text' ),
				'title' => $sktemplate->msg( 'standarddialogs-create-button-new-subpage-title' ),
				'href' => '',
				'class' => 'new-subpage'
			];
		}

		$sktemplate->getOutput()->addModules( 'ext.standardDialogs' );
	}
}
