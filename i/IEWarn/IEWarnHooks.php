<?php
/* 
 * IEWarn---displays a warning for Internet Explorer users
 * Copyright (C) 2017 Matthew Trescott
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 * 
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

class IEWarnHooks
{
    public static function onBeforePageDisplay(OutputPage &$out, Skin &$skin)
    {
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'Trident') !== false)
        {
            $browser = 'Internet Explorer';
        }
        elseif (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') !== false)
        {
            $browser = 'Internet Explorer';
        }
        elseif (strpos($_SERVER['HTTP_USER_AGENT'], 'Edge') !== false)
        {
            $browser = 'Microsoft Edge';
        }
        else
        {
            $browser = 'OK';
        }
        
        if ($browser !== 'OK')
        {
            $outerBoxStyle = '#iewarn-outer-box {' . $GLOBALS['wgIEWarnOuterBoxStyle'] . '}';
            $innerMessageStyle = '#iewarn-inner-message {' . $GLOBALS['wgIEWarnInnerMessageStyle'] . '}';
            
            $out->addInlineStyle($outerBoxStyle);
            $out->addInlineStyle($innerMessageStyle);
            
            $html = '<div id="iewarn-outer-box">';
            $html .= $GLOBALS['wgIEWarnCustomOuterBoxTitle'] ?: wfMessage('iewarn-ie-title')->text();
            $html .= '</div>';
            
            $html .= '<div id="iewarn-inner-message">';
            $html .= $GLOBALS['wgIEWarnCustomMessage'] ?: wfMessage('iewarn-ie-message', $browser)->text();
            $html .= '</div>';
            
            $out->prependHTML($html);
        }
    }
}
