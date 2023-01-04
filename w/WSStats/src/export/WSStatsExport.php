<?php
/**
 * Created by  : Wikibase Solutions
 * Project     : WSStats
 * Filename    : WSStatsExport.class.php
 * Description :
 * Date        : 6-4-2021
 * Time        : 13:37
 *
 * @version 0.8.0 2021
 *
 * @author Sen-Sai
 */

namespace WSStats\export;

use WSStats\WSStatsHooks, extensionRegistry;

/**
 * Class WSStatsExport
 */
class WSStatsExport {

	/**
	 * @param \Wikimedia\Rdbms\IResultWrapper $q
	 * @param int $pId
	 *
	 * @return string
	 */
	public function renderTable( \Wikimedia\Rdbms\IResultWrapper $q, int $pId ) : string {
		$data = "{| class=\"sortable wikitable smwtable jquery-tablesorter\"\n";
		if ( $pId !== 0 ) {
			$data .= "! " . wfMessage( 'wsstats-page-date' )->text() . "\n";
			$data .= "! " . wfMessage( 'wsstats-page-hits' )->text() . "\n";
		} else {
			$data .= "! " . wfMessage( 'wsstats-page-id' )->text() . "\n";
			$data .= "! " . wfMessage( 'wsstats-page-title' )->text() . "\n";
			$data .= "! " . wfMessage( 'wsstats-page-hits' )->text() . "\n";
		}
		while ( $row = $q->fetchRow() ) {
			$pTitle = WSStatsHooks::getPageTitleFromID( $row['page_id'] );
			if ( !is_null( $pTitle ) ) {
				$data .= "|-\n";
				if ( $pId !== 0 ) {
					$data .= "| " . $row['Date'] . "\n";
					$data .= "| " . $row['count'] . "\n";
				} else {
					$data .= "| " . $row['page_id'] . "\n";
					$data .= "| " . $pTitle . "\n";
					$data .= "| " . $row['count'] . "\n";
				}
				$data .= "|-\n";
			}
		}
		$data .= "|}\n";

		return $data;
	}

	/**
	 * @param \Wikimedia\Rdbms\IResultWrapper $q
	 * @param int $pId
	 *
	 * @return string
	 */
	public function renderCSV( \Wikimedia\Rdbms\IResultWrapper $q, $pId ) : string {
		$data = '';
		if ( $pId === 0 ) {
			while ( $row = $q->fetchRow() ) {
				$data .= $row['page_id'] . ";" . $row['count'] . ",";
			}
		} else {
			while ( $row = $q->fetchRow() ) {
				$data .= $row['Date'] . ";" . $row['count'] . ",";
			}
		}

		return rtrim(
			$data,
			','
		);
	}

	/**
	 * @param string $name Name of the extension
	 *
	 * @return mixed
	 */
	private function extensionInstalled( $name ) {
		return extensionRegistry::getInstance()->isLoaded( $name );
	}

	/**
	 * @param mysqli_result $q
	 * @param string $wsArrayVariableName
	 * @param int $pId
	 *
	 * @return string
	 */
	public function renderWSArrays(
		\Wikimedia\Rdbms\IResultWrapper $q,
		string $wsArrayVariableName,
		int $pId
	) : string {
		global $IP;
		if ( !$this->extensionInstalled( 'WSArrays' ) ) {
			return "";
		}
		if ( file_exists( $IP . '/extensions/WSArrays/ComplexArrayWrapper.php' ) ) {
			include_once( $IP . '/extensions/WSArrays/ComplexArrayWrapper.php' );
		} else {
			return "";
		}
		$wsWrapper = new \ComplexArrayWrapper();
		$result    = [];
		$t         = 0;
		while ( $row = $q->fetchRow() ) {
			$pTitle = WSStatsHooks::getPageTitleFromID( $row['page_id'] );
			if ( $pTitle !== null ) {
				if ( $pId === 0 ) {
					$result[$t][wfMessage( 'wsstats-page-id' )->text()] = $row['page_id'];
					$result[$t][wfMessage( 'wsstats-page-title' )->text()] = $pTitle;
					$result[$t][wfMessage( 'wsstats-page-hits' )->text()] = $row['count'];
				} else {
					$result[$t][wfMessage( 'wsstats-page-date' )->text()] = $row['Date'];
					$result[$t][wfMessage( 'wsstats-page-hits' )->text()] = $row['count'];
				}
				$t++;
			}
		}

		$wsWrapper->on( $wsArrayVariableName )->set( $result );

		return "";
	}

}