<?php

$cfg = require __DIR__ . '/../vendor/mediawiki/mediawiki-phan-config/src/config.php';

$cfg['directory_list'] = array_merge(
	$cfg['directory_list'], [
		'../../extensions/CreateWiki',
	]
);

$cfg['exclude_analysis_directory_list'] = array_merge(
	$cfg['exclude_analysis_directory_list'], [
		'../../extensions/CreateWiki',
	]
);

$cfg['suppress_issue_types'] = array_merge(
	$cfg['suppress_issue_types'], []
);

$cfg['scalar_implicit_cast'] = true;

return $cfg;