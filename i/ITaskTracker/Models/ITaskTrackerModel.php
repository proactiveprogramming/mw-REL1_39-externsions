<?php
/**
 * IssueTrackerIbugModel abstract class.
 */
abstract class ITaskTrackerModel
{
	/**
	 * Instance of the Database class.
	 * @var Database
	 */
	protected $_dbr = null;
	
	/**
	 * Class constructor.
	 *
	 * @param int $db
	 */
	public function __construct($db = DB_SLAVE)
	{
		$this->_dbr =& wfGetDB($db);
	}
}
?>
