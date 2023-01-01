<?php

/**
 * Initialization file for the Data Visualizer extension.
 *
 * Documentation:	 		http://www.mediawiki.org/wiki/Extension:Data_Visualizer
 * Support					http://www.mediawiki.org/wiki/Extension_talk:Data_Visualizer
 * Source code:             http://svn.wikimedia.org/viewvc/mediawiki/trunk/extensions/DataVisualizer
 *
 * @file DataVisualizer.php
 * @ingroup DataVisualizer
 *
 * @licence MIT
 * @author Nischay Nahata < nischayn22@gmail.com >
 */

/**
 * This documentation group collects source code files belonging to Data Visualizer.
 *
 * @defgroup DataVisualizer DataVisualizer
 */

if ( !defined( 'MEDIAWIKI' ) ) {
	die( 'Not an entry point.' );
}

define( 'DataVisualizer_VERSION', '0.1' );

$wgExtensionCredits['other'][] = array(
	'path' => __FILE__,
	'name' => 'Data Visualizer',
	'version' => DataVisualizer_VERSION,
	'author' => array(
		'[http://www.mediawiki.org/wiki/User:Nischayn22 Nischay Nahata] for [http://www.wikiworks.com WikiWorks]',
	),
	'url' => 'https://www.mediawiki.org/wiki/Extension:Data_Visualizer',
	'descriptionmsg' => 'DataVisualizer-desc'
);

$useExtensionPath = version_compare( $wgVersion, '1.16', '>=' ) && isset( $wgExtensionAssetsPath ) && $wgExtensionAssetsPath;
$egDataVisualizerScriptPath = ( $useExtensionPath ? $wgExtensionAssetsPath : $wgScriptPath . '/extensions' ) . '/DataVisualizer';
$egDataVisualizerIP = dirname( __FILE__ );
unset( $useExtensionPath );

$wgHooks['ParserFirstCallInit'][] = 'wfDVParserInit';

function wfDVParserInit( Parser $parser ) {
	$parser->setHook( 'dv_d3_tree', 'wfDvD3TreeRender' );
	return true;
}

//TODO: move this somewhere else
//Example: <dv_d3_tree name="Filters we have" size="2">name=age|link=lol|size=1,name=country|link=lolagain|size=1</dv_d3_tree>
function wfDvD3TreeRender( $input, array $args, Parser $parser, PPFrame $frame ) {
	$children_str = explode( ',', $input );
	$children = array();
	foreach( $children_str as $child_str)
	{
		$child = array();
		$keyvalues_str = explode( '|', $child_str );
		foreach( $keyvalues_str as $keyvalue_str )
		{
			$keyvalue = explode( '=', $keyvalue_str );
			$child[$keyvalue[0]] = $keyvalue[1];
		}
		$children[] = $child;
	}

	$data = array(
		'name' => $args['name'] ? str_replace( ' ', '_', $args['name'] ) : "Browser",
		'children'=> $children,
		'size' => $args['size'] ? $args['size'] : 1
	);

	return DataVisualizerAPI::getHTMLForTree($data);
}

$wgExtensionMessagesFiles['DataVisualizer'] = $egDataVisualizerIP . '/DataVisualizer.i18n.php';

$wgAutoloadClasses['DataVisualizerAPI'] = $egDataVisualizerIP . '/DataVisualizerAPI.php';

$wgResourceModules['ext.DataVisualizer'] = array(
	// JavaScript and CSS styles.
	'scripts' => array( 'libraries/d3/d3.v3.min.js', 'js/mediawiki.d3.js' ),
	'styles' => array( 'css/style.css' ),
	'localBasePath' => dirname( __FILE__ ),
	'remoteExtPath' => 'DataVisualizer'
);
