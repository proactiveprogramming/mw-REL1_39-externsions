<?php
/**
 * MediaWiki MathLaTeX extension
 *
 * @file
 * @name SpecialMathLaTeX
 * @version 1.0
 * @author Jesse B. Dooley
 * @date January 8, 2016
 * @ingroup Extensions
 * @brief Render mathematical equations on a wiki article using
 * LaTeX installed by cygwin.
 *
 * @section LICENSE
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 3 of
 * the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * General Public License for more details at
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @see README
 * @see VERSION
 * @see COPYING
 *
 */

if ( !defined( 'MEDIAWIKI' ) ) {
	die( "This is not a valid entry point to MediaWiki./n" );
}

# Special Settings
$wgAutoloadClasses['SpecialMathLaTeX'] = __DIR__ . '/' . 'SpecialMathLaTeX.body.php';
$wgSpecialPages['MathLaTeX'] = 'SpecialMathLaTeX';
$wgSpecialPageGroups['MathLaTeX'] = 'pagetools';
$wgGroupPermissions['sysop']['mathlatex'] = true;
$wgAvailableRights[] = 'mathlatex';

# wgMessage Settings
$wgMessageDirs['MathLaTeX'] = __DIR__ . '/' . 'i18n';
$wgExtensionMessagesFiles['MathLaTeX'] = __DIR__ . '/MathLaTeX.i18n.php';
$wgExtensionMessagesFiles['MathLaTeXAlias'] = __DIR__ . '/MathLaTeX.alias.php';


?>