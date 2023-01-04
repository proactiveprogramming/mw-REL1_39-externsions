<?php
/**
 * Hooks for MarkImages
 *
 * @file
 * @ingroup Extensions
 * @license MIT
 */
class MarkImagesHooks
{
	/**
	 * LinksUpdateComplete hook handler.
	 * Updates CSS class cache for an image.
	 * @param LinksUpdate $linksUpdate
	 */
    public static function onLinksUpdateComplete(LinksUpdate &$linksUpdate)
    {
        $title = $linksUpdate->getTitle();
        if ($title->getNamespace() != 6)
            return;     //not a file, ignore

        $classes = MarkImages::getClasses($title);
        MarkImagesDB::updateClasses($title, implode(' ', $classes));
    }

	/**
	 * InfoAction hook handler.
	 * Appends information about CSS classes assigned to a file.
	 * @param IContextSource $context
	 * @param $pageInfo
	 */
    public static function onInfoAction(IContextSource $context, &$pageInfo)
    {
        $title = $context->getTitle();
        if ($title->getNamespace() != 6)
            return;     //not a file, ignore

        $classes = MarkImagesDB::getClassesFromDB($title);

        $pageInfo['header-basic'][] = [
            $context->msg('markImages-classes-label'),
            $classes
        ];
    }

	/**
	 * Ugly as all hell BeforePageDisplay handler.
	 * Adds css classes to thumbs in galleries.
	 * @param OutputPage $out
	 * @param Skin $skin
	 */
    public static function onBeforePageDisplay(OutputPage $out, Skin $skin)
    {
        $title = $out->getTitle();
        $ns = $title->getNamespace();

        if ($ns != 14 && $ns != -1)
            return;     //we are interested only in special pages and categories

        $html = $out->getHTML();
        $gStart = strpos($html, "<ul class=\"gallery");

	    if (strpos($html, "id=\"mw-undelete-revision\""))
		    return;     //yeah, don't run it on restored page preview... that's a bad idea
	    //also, that is an ugly hack, as MW is horrible at tracking special pages and their naming is just... ugh.

        if ($gStart == false) return;     //no gallery here

        $gallery = substr($html, $gStart);
        $gEnd = strpos($gallery, "</ul>");
        $gallery = substr($gallery, 0, $gEnd + 5);

        foreach (explode('</li>', $gallery) as $li) {
                if (strlen($li) < 10) continue;
                try {
                    preg_match("/(?<=title=\").*?(?=\">)/", $li, $matches);
                    if (sizeof($matches) < 1) continue;
                    $fileName = $matches[0];
                    $title = Title::newFromText($fileName);
                    if (!$title) continue;
                    $classes = MarkImagesDB::getClassesFromDB($title);
                    $newLi = str_replace(
                        "<div class=\"thumb\"",
                        "<div class=\"thumb $classes\"",
                        $li
                    );

                    $html = str_replace(
                        $li,
                        $newLi,
                        $html
                    );
                } catch (Exception $e) { }
            }

        $out->clearHTML();
        $out->addHTML($html);
    }
}
