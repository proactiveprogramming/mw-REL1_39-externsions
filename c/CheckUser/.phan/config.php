<?php

$cfg = require __DIR__ . '/../vendor/mediawiki/mediawiki-phan-config/src/config.php';

$cfg['directory_list'] = array_merge(
	$cfg['directory_list'],
	[
		'../../extensions/Renameuser',
		'../../extensions/CentralAuth',
		'../../extensions/EventLogging',
		'../../extensions/GuidedTour',
		'../../extensions/GlobalBlocking'
	]
);

$cfg['exclude_analysis_directory_list'] = array_merge(
	$cfg['exclude_analysis_directory_list'],
	[
		'../../extensions/Renameuser',
		'../../extensions/CentralAuth',
		'../../extensions/EventLogging',
		'../../extensions/GuidedTour',
		'../../extensions/GlobalBlocking'
	]
);

return $cfg;
