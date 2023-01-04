<?php
use \MediaWiki\Minerva\Menu\Group;

class MinervaMenuHooks 
{
	public function onMobileMenu( 
        $name,
        Group &$group
    ) {
        global $wgMinervaMenuPage;
        global $wgMobileSidebar;

        if(!isset($wgMobileSidebar)) {
            $mobileSidebarArticle = new Article(Title::newFromText($wgMinervaMenuPage ?? 'MediaWiki:MobileSidebar'));
            $mobileSidebarContent = $mobileSidebarArticle->getPage()->getContent();
            $wgMobileSidebar = ContentHandler::getContentText($mobileSidebarContent);
        }

        if($wgMobileSidebar) {
            $menu = explode("\n", $wgMobileSidebar);
            $menuData = [];

            $currentSection = null;
            foreach($menu as $item) {
                if(substr($item, 0, 2) == '**') {
                    if($currentSection === null) {
                        $currentSection = '*discovery';
                    }
                    $menuData[substr($currentSection, 1)][] = substr($item, 2);
                }else if(substr($item, 0, 1) == '*') {
                    $currentSection = $item;
                }
            }
        }

        foreach($menuData as $section => $items) {
            if($section == $name) {
                $i = 0;
                foreach($items as $item) {
                    $itemData = explode("|", $item);
                    $group	->insert($name . '-menu-' . $i)
                            ->addComponent(
                                $itemData[1],
                                $itemData[0],
                                MobileUI::iconClass( ' mw-ui-icon-minerva-unStar', 'before' ),
                                [
                                    
                                ]   
                            );
                    $i ++;
                }
            }
        }
    }
}
