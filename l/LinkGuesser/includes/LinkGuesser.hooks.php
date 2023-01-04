<?php
namespace MediaWiki\Extension\LinkGuesser;

use MediaWiki\Linker\LinkRenderer;
use MediaWiki\Linker\LinkTarget;
use Title;

class LinkGuesserHooks {
    public static function onHtmlPageLinkRendererEnd(
        LinkRenderer $linkRenderer,
        LinkTarget $target,
        $isKnown, // change to bool $isKnown when possible
        &$text,
        &$attribs,
        &$ret
    ) {
        // If it's not broken, ignore
        if ( $isKnown ) {
            return true;
        }

        // Start to inspect LinkTarget object
        // If it's external, it's outside our scope, ignore
        if ( $target->isExternal() ) {
            return true;
        }

        // For an internal link, get its namespace and DB key (e.g. Monsoon in Event:Monsoon)
        $intendedTargetNamespace = $target->getNamespace();
        $intendedTargetPageKey = $target->getDBkey();

        // Swap link to point to Special:ResolveLink
        $resolveLinkSpecialPageTitle = Title::newFromText(
            'ResolveLink',
            NS_SPECIAL
        );
        $attribs['href'] = $resolveLinkSpecialPageTitle->getLinkURL(
            [
                'ns' => $intendedTargetNamespace,
                'pg' => $intendedTargetPageKey
            ]
        );
        
        return true;
    }
}