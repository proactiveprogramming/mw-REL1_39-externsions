<?php

/**
 * To the extent possible under law,  I, Mark Hershberger, have waived all copyright and
 * related or neighboring rights to Hello World. This work is published from the
 * United States.
 *
 * @copyright CC0 http://creativecommons.org/publicdomain/zero/1.0/
 * @author Mark A. Hershberger <mah@everybody.org>
 * @ingroup Maintenance
 */

if ( getenv( 'MW_INSTALL_PATH' ) ) {
	$IP = getenv( 'MW_INSTALL_PATH' );
} else {
	$IP = dirname(__FILE__).'/../../..';
}

require_once ("$IP/maintenance/Maintenance.php" );

class equationCensus extends Maintenance {

	public function __construct() {
		parent::__construct();
		$this->mDescription = "List files owned by MW_MATH and how many pages link to each sorted by link count.";
		$this->setBatchSize( 1000 );
	}

	public function execute() {
		echo "equationCensus\n";
				$start = '';
		$dbr = wfGetDB( DB_SLAVE );
		$numImages = 0;
		$output = '';


			$res = $dbr->select( 'image', 'img_name',	array( 'img_user_text' => 'MW MATH'  ), __METHOD__, array( 'ORDER BY' => 'img_name', 'LIMIT' => $this->mBatchSize ) 	);

			foreach( $res as $row ) {
				$numImages++;
				// count the pages this image is linked to.
				$lcount = 0;
				$link_count = $dbr->select( 'imagelinks', '*',  array( 'il_to' => $row->img_name ), __METHOD__, array( 'LIMIT' => $this->mBatchSize ));
				foreach( $link_count as $links ) {
					$lcount++;
				}
				$output .= $row->img_name . " " . $lcount.  "\n";
			}

		$this->output( "Output: \nFilename Page_Count\n" . $output . "\n" );
		$this->output( "File Count: " . $numImages . "\n" );

	} // execute
} // equationCensus

$maintClass = 'equationCensus';

require_once RUN_MAINTENANCE_IF_MAIN;
?>