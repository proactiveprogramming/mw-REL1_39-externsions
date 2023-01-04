<?php
if (!defined ('MEDIAWIKI')) exit (1); // not a valid entry point for MediaWiki

$wgExtensionCredits['specialpage'][] = array
  ('path' => __FILE__,
   'name' => 'ListItemFilter',
   'author' => 'Tim Janik',
   'url' => 'http://testbit.eu/MediaWiki_Extension_ListItemFilter',
   'descriptionmsg' => 'Filter list items in a wiki page for certain keys',
   'version' => '0.0.1',
   );
$wgAutoloadClasses['SpecialListItemFilter'] = dirname (__FILE__) . '/Special.php';     // auto-load extension class
$wgExtensionMessagesFiles['ListItemFilter'] = dirname (__FILE__) . '/Special.i18n.php';       // load extension messages file
$wgExtensionMessagesFiles['ListItemFilterAlias'] = dirname (__FILE__) . '/Special.alias.php'; // load extension aliases file
$wgSpecialPages['ListItemFilter'] = 'SpecialListItemFilter';  // enter the list of special page extensions
$wgSpecialPageGroups['ListItemFilter'] = 'other';	// Special pages group to enlist
