<?php
/**
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty ofv
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 */

namespace MediaWiki\Extension\PlaceNewSection;

use Language;
use MagicWord;
use MediaWiki\MediaWikiServices;
use StringUtils;

class Hooks
{

    private static function placeNewSectionInitRegex(MagicWord $mw)
    {
        $case = $mw->mCaseSensitive ? '' : 'iu';
        return "/({$mw->getBaseRegex()})/{$case}";
    }

    /**
     * @param array &$doubleUnderscoreIDs
     */
    public static function onGetDoubleUnderscoreIDs( &$doubleUnderscoreIDs )
    {
        $doubleUnderscoreIDs[] = 'addnewsectionbelow';
        $doubleUnderscoreIDs[] = 'addnewsectionabove';
    }

    /**
     * @param $content
     * @param $oldtext
     * @param $subject
     * @param $text
     * @return bool
     */
    public static function onPlaceNewSection($content, $oldtext, $subject, &$text )
    {
        // hat tip: https://github.com/staspika/mediawiki-numberedheadings/pull/3
        // see also: https://github.com/wikimedia/mediawiki/commit/112b6f3 (removal for 1.35)
        $mwf = MediaWikiServices::getInstance()->getMagicWordFactory();
        $mw = $mwf->get( 'addnewsectionbelow' );
        if ($mw->match($oldtext)) {https://github.com/wikimedia/mediawiki/commit/112b6f3
            $regexp = self::placeNewSectionInitRegex($mw);
            $text = preg_replace($regexp, '$1' . StringUtils::escapeRegexReplacement("\n{$subject}{$text}"), $oldtext, 1);
            return false;
        }
        $mw = $mwf->get('addnewsectionabove');
        if ($mw->match($oldtext)) {
            $regexp = self::placeNewSectionInitRegex($mw);
            $text = preg_replace($regexp, StringUtils::escapeRegexReplacement("{$subject}{$text}\n") . '$1', $oldtext, 1);
            return false;
        }
        return true;
    }

}
