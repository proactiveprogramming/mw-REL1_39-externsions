<?php

namespace BlueSpice\Bookshelf\Hook\LoadExtensionSchemaUpdates;

use BlueSpice\Hook\LoadExtensionSchemaUpdates;

class AddConvertContentModel extends LoadExtensionSchemaUpdates {
	protected function doProcess() {
		// UserBookContentLanguage must be executed before the conversion to book content model!
		$this->updater->addPostDatabaseUpdateMaintenance( \UserBookContentLanguage::class );
		$this->updater->addPostDatabaseUpdateMaintenance( \ConvertContentModel::class );
		$this->updater->addPostDatabaseUpdateMaintenance( \FixUserSubpageContentModel::class );
		return true;
	}

}
