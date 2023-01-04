<?php

$cfg = require __DIR__ . '/../vendor/mediawiki/mediawiki-phan-config/src/config.php';

// Multiple warnings related to \phpFlickr
$cfg['suppress_issue_types'][] = 'PhanUndeclaredClassMethod';

return $cfg;
