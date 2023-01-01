<?php

 if ( function_exists( 'wfLoadExtension' ) ) {
 	wfLoadExtension( 'ForceLogin' );

  $wgMessagesDirs['ForceLogin'] = __DIR__ . '/i18n';

 	return true;
 } else {
 	die('');
 }
