# MediaWiki-CopyWatchers

A MediaWiki extension which allows a pages editor to add the watchers of one page to the page being edited using the parser function #copywatchers

## Notes

To-dos, random thoughts, things to consider...

### Possible considerations

* When saving a page with no (or few) watchers, promt with "would you
like to add watchers?" and give suggestions basd on content.

## Code Considerations

### Possible Hooks to use:
* Manual:Hooks/AlternateUserMailer (MW 1.19+)
* Manual:Hooks/AbortEmailNotification (MW 1.20+)

### Manual:Hooks/ArticleSave
Use this for MW 1.20 and earlier

####Define Function:
public static function onArticleSave( &$article, &$user, &$text, &$summary,
$minor, $watchthis, $sectionanchor, &$flags, &$status ) { ... }

####Attach hook:
$wgHooks['ArticleSave'][] = 'MyExtensionHooks::onArticleSave';


### Manual:Hooks/PageContentSave

Use this for MW 1.21 and later

####Define function:
public static function onPageContentSave( $wikiPage, $user, $content, $summary,
$isMinor, $isWatch, $section ) { ... }


####Attach Hook:
$wgHooks['PageContentSave'][] = 'MyExtensionHooks::onPageContentSave';

