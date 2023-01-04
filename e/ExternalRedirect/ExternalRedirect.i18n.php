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

$messages = array();
$messages['en'] = array(
    'externalredirect-text' => 'This page redirects to an external site: [$1 $1]',
    'externalredirect-invalidurl' => '== ExternalRedirect Error ==
Redirection URL is invalid.',
    'externalredirect-denied' => '== ExternalRedirect Error ==
External redirection is not enabled for this namespace, page or URL.',
    'externalredirect-denied-url' => 'Intended redirection URL: [$1 $1]',
);

$messages['de'] = array(
    'externalredirect-text' => 'Diese Seite leitet auf die folgende externe Webseite um: [$1 $1]',
);


$magicWords = array();
$magicWords['en'] = array(
    'externalredirect' => array( 0, 'externalredirect' ),
);
