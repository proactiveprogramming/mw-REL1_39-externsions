<?php
	header("Content-Type: text/plain");
	
	@require_once "./JobSchEd.loader.php";
	$strThisDir = rtrim(dirname(__FILE__), "/\ ");
	$oLoader = new ecSimpleJSLoader($strThisDir);
	
	// test loader
	$oLoader->noCache = true;
	$oLoader->strBaseModulesName = 'loader_';
	$oLoader->strMiniModulesName = 'loader.mini.js';
	$strMiniTestFile = $oLoader->createMiniModules(array(
		'test',
	));
	//$wgOut->addHeadItem('LoaderMiniTest', Html::linkedScript("{$strThisDir}/{$strMiniTestFile}"));
	readfile($strMiniTestFile);
?>