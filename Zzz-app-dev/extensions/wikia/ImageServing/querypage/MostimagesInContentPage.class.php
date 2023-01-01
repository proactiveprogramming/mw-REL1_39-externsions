<?php

/**
 * Query page class used to generate cached data for ImageServing
 * image popularity ranking
 *
 * This list is similar to one generated by MW's core Mostimages, but only
 * counts inclusions in pages in $wgContentNamespaces
 *
 * @author macbre
 */
class MostimagesInContentPage extends MostimagesPage {
	function __construct( $name = 'MostLinkedFilesInContent' ) {
		parent::__construct( $name );
	}

	/**
	 * Force an old name of the special page here.
	 * We need to keep the old name in querycache's qc_type column to keep ImageServing working
	 *
	 * @see PLATFORM-1007
	 *
	 * @return String
	 */
	function getName() {
		return 'MostimagesInContent';
	}

	function getQueryInfo() {
		global $wgContentNamespaces;

		return array (
			'tables' => array ( 'imagelinks', 'page' ),
			'fields' => array ( "'" . NS_FILE . "' AS namespace",
				'il_to AS title',
				'COUNT(*) AS value' ),
			'options' => array ( 'GROUP BY' => 'il_to',
				'HAVING' => 'COUNT(*) > 1' ),
			// include pages from content namespaces only
			'conds' => array ( 'page_namespace' => $wgContentNamespaces ),
			'join_conds' => array ( 'page' => array ( 'LEFT JOIN',
				array ( 'page.page_id = il_from' ) ) )
		);
	}
}