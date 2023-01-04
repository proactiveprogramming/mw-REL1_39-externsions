<?php
/* ExternalRedirect - MediaWiki extension to allow redirects to external sites.
 * Copyright (C) 2013-2022 Davis Mosenkovs
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 */

if(!defined('MEDIAWIKI'))
    die();

$wgExtensionCredits[ 'parserhook' ][] = array(
    'path' => __FILE__,
    'name' => 'ExternalRedirect',
    'author' => 'Davis Mosenkovs',
    'url' => 'https://www.mediawiki.org/wiki/Extension:ExternalRedirect',
    'description' => 'Allows to make redirects to external websites',
    'version' => '1.2.1',
);

$wgExtensionMessagesFiles['ExternalRedirect'] = dirname( __FILE__ ) . '/ExternalRedirect.i18n.php';

/*** Default configuration ***/
// Array with NUMERIC namespace IDs where external redirection should be allowed.
$wgExternalRedirectNsIDs = array();

// Better avoid. Array with page names (see magic word {{FULLPAGENAME}}) where external redirection should be allowed.
$wgExternalRedirectPages = array();

// Avoid or be extremely careful. Use whitelisting approach and very precise expressions. PCRE regex used to determine whether redirection to particular target URL is allowed.
$wgExternalRedirectURLRegex = '';

// Whether to display link to redirection URL (along with error message) in case externalredirect is used where it is not allowed.
$wgExternalRedirectDeniedShowURL = false;
/*****************************/

$wgHooks['ParserFirstCallInit'][] = 'wfExternalRedirectParserInit';

function wfExternalRedirectParserInit( Parser $parser ) {
    $parser->setFunctionHook( 'externalredirect', 'wfExternalRedirectRender');
    return true;
}

function wfExternalRedirectRender($parser, $url = '') {
    global $wgCommandLineMode, $wgExternalRedirectNsIDs, $wgExternalRedirectPages, $wgExternalRedirectURLRegex, $wgExternalRedirectDeniedShowURL;
    $parser->getOutput()->updateCacheExpiry(0);
    if(!wfParseUrl($url) || strpos($url, chr(13))!==false || strpos($url, chr(10))!==false || strpos($url, chr(0))!==false) {
        return wfMessage('externalredirect-invalidurl')->text();
    }
    if((in_array($parser->getTitle()->getNamespace(), $wgExternalRedirectNsIDs, true) 
      || in_array($parser->getTitle()->getPrefixedText(), $wgExternalRedirectPages, true))
      && ($wgExternalRedirectURLRegex==='' || preg_match($wgExternalRedirectURLRegex, $url)===1)) {
        if($wgCommandLineMode!==true) {
            header('Location: '.$url);
        }
        return wfMessage('externalredirect-text', $url)->text();
    } else {
        return wfMessage('externalredirect-denied')->text().($wgExternalRedirectDeniedShowURL 
          ? ' '.wfMessage('externalredirect-denied-url', $url)->text() : "");
    }
}
