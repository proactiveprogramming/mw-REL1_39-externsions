<?php

namespace BlueSpice\Bookshelf\Hook\BeforePageDisplay;

class AddResources extends \BlueSpice\Hook\BeforePageDisplay {

	protected function doProcess() {
		$this->out->addModules( 'ext.bluespice.bookshelf.addToBook' );

		$this->out->addModuleStyles( 'ext.bluespice.bookshelf.ui.styles' );
		$this->out->addModuleStyles( 'ext.bluespice.bookshelf.navigationTab.styles' );
		$this->out->addModules( 'ext.bluespice.bookshelf.navigationTab' );
		$this->out->addModuleStyles( 'ext.bluespice.bookshelf.pager.navigation.styles' );

		if ( !$this->skin->getUser()->isAnon() ) {
			$location = $this->skin->msg(
				'bs-bookshelf-personal-books-page-prefix',
				$this->skin->getUser()->getName()
			);
			$this->out->addJsConfigVars(
				'bsgBookshelfUserBookLocation',
				$this->skin->getUser()->getUserPage()->getNsText()
					. ':' . $location->inContentLanguage()->parse()
			);
		}
		$config = $this->getConfig();
		$pagerBeforeContent = $config->get( 'BookShelfShowChapterNavigationPagerBeforeContent' );
		$pagerAfterContent = $config->get( 'BookShelfShowChapterNavigationPagerAfterContent' );

		if ( ( $pagerBeforeContent === true ) || ( $pagerBeforeContent === 1 ) ) {
			$this->out->addModuleStyles( 'ext.bluespice.bookshelf.pager.before-content.styles' );
		}

		if ( ( $pagerAfterContent === true ) || ( $pagerAfterContent === 1 ) ) {
			$this->out->addModuleStyles( 'ext.bluespice.bookshelf.pager.after-content.styles' );
		}
	}

}
