# mediawiki-extensions-magicallinkers

The MagicalLinkers extension provides dynamic linking of inline keywords, such as [magic links](https://www.mediawiki.org/wiki/Help:Magic_links). 
Following the discussion around the the [future of magic links](https://www.mediawiki.org/wiki/Requests_for_comment/Future_of_magic_links) (more specifically, its removal from mediawiki core), 'wsd' (untraced) 
submitted a [patch](https://phabricator.wikimedia.org/T28207#294990) to demonstrate how magiclinks could be turned into an extension. 
In the meantime, [$wgEnableMagicLinks](https://www.mediawiki.org/wiki/Manual:$wgEnableMagicLinks) was introduced to aid in phasing these out, but the possibility of extending the list was not there.

This extension is nothing but the extension.json wrapping of the simple submitted patch.

How to use
==========
Besides normal extension insertion, you'll probably want to extend it with your own keywords. Example:
```php
wfLoadExtension( 'MagicalLinkers' );
class MagicalLinkersExt {
	public static function linkBug( $text ) {
        $urlNumber = ltrim( substr( $text, 4 ), '0');
		$url = 'https://mybugplatform.mysite.pt/issues/?id=' . $urlNumber;
		$safeUrl = $url;
		return '<a href=\'' . $safeUrl . '\' class=\'mw-magiclink-bug\'>bug ' . $urlNumber . '</a>';
	}
}
$wgMagicalLinkers = array(
	array( 
		'linker' => 'MagicalLinkersExt::linkBug',
		'pattern' => 'bug:\\s*[0-9]+'
	)
);
```
