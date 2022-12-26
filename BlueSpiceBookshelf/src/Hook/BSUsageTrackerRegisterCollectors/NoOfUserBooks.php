<?php

namespace BlueSpice\Bookshelf\Hook\BSUsageTrackerRegisterCollectors;

use BS\UsageTracker\Hook\BSUsageTrackerRegisterCollectors;

class NoOfUserBooks extends BSUsageTrackerRegisterCollectors {

	protected function doProcess() {
		$this->collectorConfig['bs:userbooks'] = [
			'class' => 'Database',
			'config' => [
				'identifier' => 'no-of-user-books',
				'descKey' => 'no-of-user-books',
				'table' => 'page',
				'uniqueColumns' => [ 'page_title' ],
				'condition' => [ 'page_namespace' => NS_USER,
				'page_title like "Book/%"'
				]
			]
		];
	}

}
