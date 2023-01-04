<?php

// Allow setting language per page
$wgPageLanguageUseDB = true;
$wgGroupPermissions['user']['pagelang'] = true;

// Connect a Speechoid instance
$wgWikispeechSpeechoidUrl = 'https://wikispeech-tts-dev.wmflabs.org/';
$wgWikispeechSpeechoidHaproxyQueueUrl = 'https://wikispeech-tts-dev.wmflabs.org/';

// Used to map between symbolsets
$wgWikispeechSymbolSetUrl = 'https://wikispeech-symbolset-dev.wmcloud.org/';

// Only allow logged in users to listen, to check permissions are respected
$wgGroupPermissions['*']['wikispeech-listen'] = false;
$wgGroupPermissions['user']['wikispeech-listen'] = true;
