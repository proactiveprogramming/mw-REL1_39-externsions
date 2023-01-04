<?php

// Inspired from "mediawiki-extensions-WikimediaMessages"

use MediaWiki\Hook\SkinAddFooterLinksHook;

class SimpleFooterLink implements SkinAddFooterLinksHook
{
    public function __construct()
    {

    }
    public static function factory(): SimpleFooterLink
    {
        return new self();
    }
    /**
     * Add links that system administrators want to the footer of every page
     *
     * @see https://www.mediawiki.org/wiki/Manual:Hooks/SkinAddFooterLinks
     *
     * @param Skin $skin
     * @param string $key
     * @param array &$footerLinks
     * @return bool|void True or no return value to continue or false to abort
     */
    public function onSkinAddFooterLinks(Skin $skin, string $key, array &$footerLinks)
    {
        if ($key !== "places") {
            return;
        }
        global $wgSimpleFooterLinkArray;
        if (!is_array($wgSimpleFooterLinkArray)) {
            return;
        }
        foreach ($wgSimpleFooterLinkArray as $footerLink) {
            if ($footerLink["tag"] === "a" && is_array($footerLink["attr"]) && array_key_exists("href", $footerLink["attr"])) {
                $href = $footerLink["attr"]["href"];
                $footerLink["attr"]["href"] = Skin::makeInternalOrExternalUrl($href);
            }
            $innerHTML = $footerLink["innerHTML"];
            $encode = mb_detect_encoding($innerHTML, ["ASCII", "UTF-8", "GB2312", "GBK", "BIG5"]);
            $footerLink["innerHTML"] = mb_convert_encoding($innerHTML, "UTF-8", $encode);
            $link = Html::rawElement(
                $footerLink["tag"],
                $footerLink["attr"],
                $footerLink["innerHTML"]
            );
            $footerLinks[$footerLink["name"]] = $link;
        }
    }
}
