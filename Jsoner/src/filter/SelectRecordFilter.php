<?php
/**
 * Created by IntelliJ IDEA.
 * User: anfink
 * Date: 15/12/16
 * Time: 13:27
 */

namespace jsoner\filter;

use jsoner\Config;

/**
 * Selects records from a list that match a key/value pair and returns them as a list.
 *
 * Class SelectRecordFilter
 * @package jsoner\filter
 */
class SelectRecordFilter implements Filter
{
	private $config;

	/**
	 * @param Config $config
	 */
	public function __construct( $config ) {

		$this->config = $config;
	}

	public static function doFilter($array, $keyValue)
	{
		list($key, $value) = explode(':', $keyValue, 2);
		$found = [];

		foreach($array as $record) {
			if (array_key_exists($key, $record) && $value === (string)$record[$key]) {
				array_push($found, $record);
			}
		}

		return $found;
	}
}
