<?php
/**
 * Copyright (c) 2012 Khaled El Mansoury
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * Links:
 * Full documentation available at: http://www.expressprogs.com/products/mwext/selecttag/
 * MediaWiki page: https://www.mediawiki.org/wiki/Extension:SelectTag
 */

if ( function_exists( 'wfLoadExtension' ) ) {
	wfLoadExtension( 'SelectTag' );
	// Keep i18n globals so mergeMessageFileList.php doesn't break
	$wgMessagesDirs['SelectTag'] = __DIR__ . '/i18n';
	wfWarn(
		'Deprecated PHP entry point used for the SelectTag extension. ' .
		'Please use wfLoadExtension() instead, ' .
		'see https://www.mediawiki.org/wiki/Special:MyLanguage/Manual:Extension_registration for more details.'
	);
	return;
} else {
	die( 'This version of the SelectTag extension requires MediaWiki 1.35+' );
}
