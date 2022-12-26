<?php
/**
 * MediaWiki InterlanguageCentral extension v1.3
 *
 * Copyright © 2010-2011 Nikola Smolenski <smolensk@eunet.rs>
 * @version 1.3
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 *
 * For more information,
 * @see https://www.mediawiki.org/wiki/Extension:Interlanguage
 */

$wgInterlanguageCentralExtensionIndexUrl = "";

$wgJobClasses['purgeDependentWikis'] = 'InterlanguageCentralExtensionPurgeJob';
$wgExtensionCredits['parserhook'][] = array(
	'name'			=> 'Interlanguage Central',
	'author'		=> 'Nikola Smolenski',
	'url'			=> 'https://www.mediawiki.org/wiki/Extension:Interlanguage',
	'version'		=> '1.4.0',
	'descriptionmsg'	=> 'interlanguagecentral-desc',
);

$wgMessagesDirs['InterlanguageCentral'] = __DIR__ . '/i18n/central';
$wgExtensionMessagesFiles['InterlanguageCentralMagic'] = dirname(__FILE__) . '/InterlanguageCentral.i18n.magic.php';
$wgAutoloadClasses['InterlanguageCentralExtensionPurgeJob'] = dirname(__FILE__) .  '/InterlanguageCentralExtensionPurgeJob.php';
$wgAutoloadClasses['InterlanguageCentralExtension'] = dirname(__FILE__) . '/InterlanguageCentralExtension.php';
$wgHooks['ParserFirstCallInit'][] = 'wfInterlanguageCentralExtension';

/**
 * @param Parser $parser
 * @return true
 */
function wfInterlanguageCentralExtension( $parser ) {
	global $wgHooks, $wgInterlanguageCentralExtension;

	if( !isset( $wgInterlanguageCentralExtension ) ) {
		$wgInterlanguageCentralExtension = new InterlanguageCentralExtension();
		$wgHooks['LinksUpdate'][] = $wgInterlanguageCentralExtension;
		$parser->setFunctionHook( 'languagelink', array( $wgInterlanguageCentralExtension, 'languagelink' ), Parser::SFH_NO_HASH );
	}
	return true;
}
