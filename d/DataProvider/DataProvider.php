<?php

$wgExtensionCredits['other'][] = array(
    'path'        => __FILE__,
    'name'        => 'DataProvider',
    'author'      => 'Vitold Sedyshev',
    'url'         => 'https://www.mediawiki.org/wiki/Extension:DataProvider',
    'description' => 'This extension provide UI to import page from MediaWiki.',
    'version'     => 1.0,
);

$wgAutoloadClasses['DataProvider'] = dirname(__FILE__) . '/includes/DataProvider.class.php';
$wgAutoloadClasses['DataProviderSpecial'] = dirname(__FILE__) . '/includes/DataProviderSpecial.class.php';
$wgAutoloadClasses['DataProviderJob'] = dirname(__FILE__) . '/includes/DataProviderJob.class.php';
$wgAutoloadClasses['DataProviderImport'] = dirname(__FILE__) . '/includes/DataProviderImport.class.php';

$instance = new DataProvider();
$wgHooks['BeforeDisplayNoArticleText'][] = array($instance, 'onBeforeDisplayNoArticleText');
$wgHooks['LoadExtensionSchemaUpdates'][] = array($instance, 'onLoadExtensionSchemaUpdates');

$wgSpecialPages['DataProvider'] = 'DataProviderSpecial';

$wgExtensionAliasesFiles['DataProvider'] = dirname(__FILE__)  . '/DataProvider.alias.php';
$wgExtensionMessagesFiles['DataProvider'] = dirname(__FILE__) . '/DataProvider.i18n.php';

$wgJobClasses['DataProviderJob'] = 'DataProviderJob';

$wgAutoloadClasses['Curl_Http'] = dirname(__FILE__) . '/includes/Curl_Http.class.php';
$wgAutoloadClasses['Curl_Http_Request'] = dirname(__FILE__) . '/includes/Curl_Http_Request.class.php';
$wgAutoloadClasses['Curl_Http_Response'] = dirname(__FILE__) . '/includes/Curl_Http_Response.class.php';

$wgAutoloadClasses['MWRequest'] = dirname(__FILE__) . '/includes/MWRequest.class.php';
$wgAutoloadClasses['MWResponse'] = dirname(__FILE__) . '/includes/MWResponse.class.php';
$wgAutoloadClasses['QueryMWResponse'] = dirname(__FILE__) . '/includes/QueryMWResponse.class.php';

