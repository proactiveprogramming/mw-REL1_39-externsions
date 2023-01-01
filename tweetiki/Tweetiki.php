<?php
$wgExtensionCredits['validextensionclass'][] = array(
       'path' => __FILE__,
       'name' => 'Tweetiki',
       'author' =>'Ashish Dubey', 
       'url' => 'https://github.com/dash1291/tweetiki', 
       'description' => 'A simple extension that allows you to tweet about your wiki edits.',
       'version'  => 0.1,
       );
$dir = dirname(__FILE__) . '/';
$wgAutoloadClasses['SpecialTweetiki'] = $dir . 'SpecialTweetiki.php';
$wgAutoloadClasses['Tweetiki'] = $dir . 'Tweetiki.ext.php';
$wgSpecialPages['Tweetiki'] = 'SpecialTweetiki';
$wgHooks['EditPageBeforeEditChecks'][] = 'Tweetiki::showTweetCheck';
$wiki_url = '<yourmediawikiurl>/ with trailing slash preserved';
$api_key = 'Your Twitter App Key';
$api_secret = 'Your Twitter App Secret';
?>
